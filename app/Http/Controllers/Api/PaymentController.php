<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Payment API (mobile / partner integrations).
 *
 * - Authorization is fail-closed (401 without a valid principal).
 * - A customer may only pay for their own booking; tenant staff may only
 *   collect for bookings inside their own tenant.
 * - Gateway webhooks are signature-verified and idempotent: replaying the
 *   same gateway reference can never double-credit a booking.
 */
class PaymentController extends ApiController
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function initiate(Request $request)
    {
        $user = $this->apiUser($request);

        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|uuid|exists:bookings,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:CASH,BANK_TRANSFER,STRIPE,JAZZCASH,EASYPAISA',
            'phone' => 'nullable|string|max:25',
            'transaction_reference' => 'nullable|string|max:100',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $booking = Booking::findOrFail($request->booking_id);

        if ($user->isCustomer()) {
            abort_unless($booking->customer_id === $user->id, 403, 'You can only pay for your own booking.');
        } else {
            $this->assertTenantAccess($booking->tenant_id, $user);
            $this->assertPermission($user, 'payments.record');
        }

        $amount = round((float) $request->amount, 2);

        // Never collect more than the outstanding balance.
        if ($amount > (float) $booking->balance_due + 0.009) {
            return response()->json([
                'message' => 'Amount exceeds the outstanding balance of PKR '.number_format((float) $booking->balance_due, 2).'.',
            ], 422);
        }

        $result = $this->paymentService->processPayment(
            $booking->id,
            $amount,
            $request->string('payment_method')->toString(),
            [
                'phone' => $request->input('phone'),
                'transaction_reference' => $request->input('transaction_reference'),
                'idempotency_key' => $request->input('idempotency_key'),
            ]
        );

        if (! $result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        $payment = $this->paymentService->createPayment(
            $booking->tenant_id,
            $booking->id,
            $amount,
            'ADVANCE',
            $request->string('payment_method')->toString(),
            $request->input('transaction_reference'),
            $user->id,
            'Payment initiated via API: '.$request->string('payment_method')->toString()
        );

        if ($request->string('payment_method')->toString() === 'CASH' || $request->string('payment_method')->toString() === 'BANK_TRANSFER') {
            $booking->advance_paid = (float) $booking->advance_paid + $amount;
            $booking->balance_due = max(0, (float) $booking->grand_total - (float) $booking->advance_paid);
            $booking->save();
        }

        return response()->json([
            'success' => true,
            'payment' => $payment,
            'gateway_data' => isset($result['gateway_url']) ? $result : null,
            // Clients should echo this on a retry so Stripe can dedupe the
            // identical request instead of creating a second intent.
            'idempotency_key' => $result['idempotency_key'] ?? null,
            'message' => $result['message'],
        ]);
    }

    public function webhook(Request $request, string $gateway)
    {
        $payload = $request->all();
        $rawBody = $request->getContent();

        $signature = strtolower($gateway) === 'stripe'
            ? $request->header('Stripe-Signature', '')
            : $request->header('X-Signature', $request->header('X-Hub-Signature-256', ''));

        if (! $this->paymentService->verifyWebhook($payload, $signature, $gateway, $rawBody)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        // Stripe signs an envelope; the values we care about live in the
        // verified payment-intent object. Other gateways post flat fields.
        if (strtolower($gateway) === 'stripe') {
            $event = json_decode($rawBody, true);
            $object = is_array($event) ? ($event['data']['object'] ?? []) : [];
            $bookingReference = $object['metadata']['booking_reference'] ?? null;
            $amount = $object['amount_received'] ?? $object['amount'] ?? 0;
            $transactionReference = $object['id'] ?? null;
        } else {
            $gatewayReference = (string) ($payload['transaction_reference'] ?? $payload['pp_TxnRef'] ?? $payload['txnRef'] ?? '');

            // Local gateways may echo only our transaction reference. It is
            // generated as "<booking-reference>-<random>", so recover the
            // booking from the stable prefix instead of treating the whole
            // installment reference as a booking reference.
            $bookingReference = $payload['booking_reference'] ?? null;
            if (! $bookingReference && preg_match('/^((?:BK-\d{6}-[A-Z0-9]{5}|RG-SB-[A-F0-9]{6}))-/', $gatewayReference, $matches) === 1) {
                $bookingReference = $matches[1];
            }
            if (! $bookingReference) {
                $bookingReference = $gatewayReference ?: null;
            }

            $amount = $payload['amount'] ?? $payload['pp_Amount'] ?? 0;
            $transactionReference = $gatewayReference ?: null;
        }

        if (! $bookingReference || ! $transactionReference) {
            return response()->json(['status' => 'ignored']);
        }

        try {
            return DB::transaction(function () use ($bookingReference, $transactionReference, $amount, $gateway) {
                // Lock the booking so concurrent webhook retries cannot both
                // read the same balance and credit it twice.
                $booking = Booking::where('booking_reference', $bookingReference)
                    ->lockForUpdate()
                    ->first();

                if (! $booking) {
                    return response()->json(['status' => 'ignored']);
                }

                $paymentMethod = strtoupper($gateway);

                if (Payment::where('payment_method', $paymentMethod)
                    ->where('transaction_reference', $transactionReference)
                    ->lockForUpdate()
                    ->exists()) {
                    return response()->json(['status' => 'already_processed']);
                }

                $value = round(((float) $amount) / 100, 2);

                if ($value <= 0 || $value > (float) $booking->balance_due + 0.009) {
                    return response()->json(['status' => 'ignored', 'reason' => 'amount_out_of_range']);
                }

                $payment = $this->paymentService->createPayment(
                    $booking->tenant_id,
                    $booking->id,
                    $value,
                    'ADVANCE',
                    $paymentMethod,
                    $transactionReference,
                    null,
                    'Webhook payment from '.$gateway
                );

                $booking->advance_paid = (float) $booking->advance_paid + (float) $payment->amount;
                $booking->balance_due = max(0, (float) $booking->grand_total - (float) $booking->advance_paid);
                $booking->save();

                return response()->json(['status' => 'processed']);
            });
        } catch (UniqueConstraintViolationException) {
            // The database is the final idempotency authority when two
            // gateways deliver the same reference at the same instant.
            return response()->json(['status' => 'already_processed']);
        }
    }

    public function summary(Request $request)
    {
        $user = $this->apiUser($request);

        abort_if(! $user->tenant_id, 403, 'No tenant assigned.');

        $period = $request->query('period', 'MONTHLY');

        return response()->json($this->paymentService->getPaymentSummary($user->tenant_id, $period));
    }

    public function list(Request $request)
    {
        $user = $this->apiUser($request);

        $query = Payment::with('booking', 'receivedByUser');

        $query = $this->scopeToPrincipal($query, $user, 'tenant_id', 'received_by_user_id');

        $payments = $query->latest()->paginate(20);

        return response()->json($payments);
    }
}
