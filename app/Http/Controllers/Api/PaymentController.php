<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function initiate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|uuid|exists:bookings,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:CASH,BANK_TRANSFER,STRIPE,JAZZCASH,EASYPAISA',
            'phone' => 'nullable|string|max:25',
            'transaction_reference' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $booking = Booking::find($request->booking_id);

        // Verify tenant access
        if ($user && ! $user->isSuperAdmin() && $booking->tenant_id !== $user->tenant_id) {
            return response()->json(['message' => 'Unauthorized access to this booking.'], 403);
        }

        $result = $this->paymentService->processPayment(
            $request->booking_id,
            (float) $request->amount,
            $request->payment_method,
            [
                'phone' => $request->phone,
                'transaction_reference' => $request->transaction_reference,
            ]
        );

        if (! $result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        // Create payment record
        $payment = $this->paymentService->createPayment(
            $booking->tenant_id,
            $booking->id,
            (float) $request->amount,
            'ADVANCE',
            $request->payment_method,
            $request->transaction_reference,
            $user?->id,
            'Payment initiated via API: '.$request->payment_method
        );

        // Update booking advance paid if cash or verified
        if ($request->payment_method === 'CASH' || $request->payment_method === 'BANK_TRANSFER') {
            $booking->advance_paid = (float) $booking->advance_paid + (float) $request->amount;
            $booking->balance_due = max(0, (float) $booking->grand_total - (float) $booking->advance_paid);
            $booking->save();
        }

        return response()->json([
            'success' => true,
            'payment' => $payment,
            'gateway_data' => isset($result['gateway_url']) ? $result : null,
            'message' => $result['message'],
        ]);
    }

    public function webhook(Request $request, string $gateway)
    {
        $payload = $request->all();
        $signature = $request->header('X-Signature', $request->header('X-Hub-Signature-256', ''));

        $isValid = $this->paymentService->verifyWebhook($payload, $signature, $gateway);

        if (! $isValid) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        // Process successful payment
        $bookingReference = $payload['booking_reference'] ?? $payload['pp_TxnRef'] ?? $payload['txnRef'] ?? null;
        $amount = $payload['amount'] ?? $payload['pp_Amount'] ?? $payload['amount'] ?? 0;

        if ($bookingReference) {
            $booking = Booking::where('booking_reference', $bookingReference)->first();

            if ($booking) {
                $payment = $this->paymentService->createPayment(
                    $booking->tenant_id,
                    $booking->id,
                    (float) $amount / 100, // Convert from paisa/cents
                    'ADVANCE',
                    strtoupper($gateway),
                    $payload['transaction_reference'] ?? $payload['pp_TxnRef'] ?? $payload['txnRef'] ?? null,
                    null,
                    'Webhook payment from '.$gateway
                );

                $booking->advance_paid = (float) $booking->advance_paid + (float) $payment->amount;
                $booking->balance_due = max(0, (float) $booking->grand_total - (float) $booking->advance_paid);
                $booking->save();

                return response()->json(['status' => 'processed']);
            }
        }

        return response()->json(['status' => 'ignored']);
    }

    public function summary(Request $request)
    {
        $user = Auth::user();

        if (! $user->tenant_id) {
            return response()->json(['message' => 'No tenant assigned.'], 403);
        }

        $period = $request->query('period', 'MONTHLY');

        $summary = $this->paymentService->getPaymentSummary($user->tenant_id, $period);

        return response()->json($summary);
    }

    public function list(Request $request)
    {
        $user = Auth::user();

        $query = Payment::where('tenant_id', $user->tenant_id)
            ->with('booking', 'receivedByUser');

        if ($request->has('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $payments = $query->latest()->paginate(20);

        return response()->json($payments);
    }
}
