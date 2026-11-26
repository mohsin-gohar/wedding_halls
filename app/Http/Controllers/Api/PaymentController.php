<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

/**
 * Manual, cash-only payment API.
 *
 * Customers can only READ their own receipts. Recording a payment or refund
 * always requires an authenticated staff principal with the matching
 * permission, tenant ownership of the booking, and a database transaction
 * that re-locks the booking before changing any balance.
 */
class PaymentController extends ApiController
{
    public function __construct(protected PaymentService $paymentService) {}

    public function record(Request $request)
    {
        $user = $this->apiUser($request);

        abort_if($user->isCustomer(), 403, 'Customers cannot record payments.');
        $this->assertPermission($user, 'payments.record');

        $validated = $request->validate([
            'booking_id' => ['required', 'uuid', 'exists:bookings,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'payment_type' => ['required', 'in:TOKEN_ADVANCE,SECOND_INSTALLMENT,FINAL_SETTLEMENT,SECURITY_DEPOSIT'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $this->assertTenantAccess($booking->tenant_id, $user);

        try {
            $payment = $this->paymentService->recordCashPayment(
                $booking,
                (float) $validated['amount'],
                $validated['payment_type'],
                $user,
                $validated['transaction_reference'] ?? null,
                $validated['notes'] ?? null
            );
        } catch (\PDOException $e) {
            report($e);

            return response()->json(['message' => 'The cash payment could not be recorded. Please try again.'], 500);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'The cash payment could not be recorded. Please try again.'], 500);
        }

        return response()->json([
            'success' => true,
            'payment' => $payment,
            'message' => 'Cash payment recorded and booking balance updated.',
        ], 201);
    }

    public function refund(Request $request)
    {
        $user = $this->apiUser($request);

        abort_if($user->isCustomer(), 403, 'Customers cannot record refunds.');
        $this->assertPermission($user, 'payments.refund');

        $validated = $request->validate([
            'booking_id' => ['required', 'uuid', 'exists:bookings,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $this->assertTenantAccess($booking->tenant_id, $user);

        try {
            $payment = $this->paymentService->recordCashRefund(
                $booking,
                (float) $validated['amount'],
                $user,
                $validated['transaction_reference'] ?? null,
                $validated['notes'] ?? null
            );
        } catch (\PDOException $e) {
            report($e);

            return response()->json(['message' => 'The cash refund could not be recorded. Please try again.'], 500);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'The cash refund could not be recorded. Please try again.'], 500);
        }

        return response()->json([
            'success' => true,
            'payment' => $payment,
            'message' => 'Cash refund recorded by authorized staff.',
        ], 201);
    }

    public function summary(Request $request)
    {
        $user = $this->apiUser($request);

        abort_if($user->isCustomer(), 403, 'Customers cannot view financial summaries.');
        $this->assertPermission($user, 'payments.view');
        abort_if(! $user->tenant_id && ! $user->isSuperAdmin(), 403, 'No tenant assigned.');

        $tenantId = $user->isSuperAdmin()
            ? $request->query('tenant_id', $user->tenant_id)
            : $user->tenant_id;

        if (! $tenantId) {
            return response()->json(['message' => 'No tenant assigned.'], 422);
        }

        return response()->json($this->paymentService->getPaymentSummary(
            (string) $tenantId,
            (string) $request->query('period', 'MONTHLY')
        ));
    }

    public function list(Request $request)
    {
        $user = $this->apiUser($request);

        // Customers may read only their own receipts below; vendor and
        // platform staff must hold the explicit financial-read permission.
        if (! $user->isCustomer()) {
            $this->assertPermission($user, 'payments.view');
        }

        $query = Payment::with('booking', 'receivedByUser');

        if ($user->isCustomer()) {
            $query->whereHas('booking', fn ($q) => $q->where('customer_id', $user->id));
        } elseif (! $user->isSuperAdmin() && ! $user->isPlatformFinance()) {
            abort_if(! $user->tenant_id, 403, 'No tenant assigned.');
            $query->where('tenant_id', $user->tenant_id);
        }

        return response()->json($query->latest()->paginate(20));
    }
}
