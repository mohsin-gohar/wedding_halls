<?php

namespace App\Http\Controllers\Api;

use App\Models\ServiceBooking;
use App\Models\ServicePayment;
use Illuminate\Http\Request;

/**
 * Service booking API (photography, catering, decoration, beauty, transport…).
 *
 * Scoped exactly like the hall booking API: tenant staff see their own
 * tenant, customers see their own bookings, nobody sees anything else.
 */
class ServiceBookingController extends ApiController
{
    public function index(Request $request)
    {
        $user = $this->apiUser($request);

        $query = ServiceBooking::with(['tenant:id,name,slug', 'vendorCategory:id,name', 'customer:id,name,email']);

        $query = $this->scopeToPrincipal($query, $user);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('event_date_from')) {
            $query->whereDate('event_date', '>=', $request->string('event_date_from')->toString());
        }

        if ($request->filled('event_date_to')) {
            $query->whereDate('event_date', '<=', $request->string('event_date_to')->toString());
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function show(Request $request, string $id)
    {
        $user = $this->apiUser($request);

        $query = ServiceBooking::with([
            'tenant:id,name,slug',
            'vendorCategory:id,name',
            'customer:id,name,email',
            'payments',
        ]);

        $query = $this->scopeToPrincipal($query, $user);

        $booking = $query->findOrFail($id);

        return response()->json($booking);
    }

    /**
     * Record a customer payment against a service booking.
     *
     * Customers may pay for their own booking; tenant staff may collect for
     * bookings inside their own tenant. Amounts above the outstanding
     * balance are rejected.
     */
    public function recordPayment(Request $request, string $id)
    {
        $user = $this->apiUser($request);

        $booking = ServiceBooking::query()->where('id', $id)->firstOrFail();

        if ($user->isCustomer()) {
            abort_unless($booking->customer_id === $user->id, 403, 'You can only pay for your own booking.');
        } else {
            $this->assertTenantAccess($booking->tenant_id, $user);
            $this->assertPermission($user, 'payments.record');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'in:JAZZCASH,EASYPAISA,BANK_TRANSFER,CARD,CASH,WALLET'],
            'kind' => ['nullable', 'in:ADVANCE,INSTALLMENT,FULL'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $amount = round((float) $validated['amount'], 2);
        $balance = $booking->balanceDue();

        if ($amount > $balance + 0.009) {
            return response()->json([
                'message' => 'Amount exceeds the outstanding balance of PKR '.number_format($balance, 2).'.',
            ], 422);
        }

        $payment = ServicePayment::create([
            'tenant_id' => $booking->tenant_id,
            'service_booking_id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'amount' => $amount,
            'method' => $validated['method'],
            'reference' => $validated['reference'] ?? null,
            'status' => 'PENDING',
            'kind' => $validated['kind'] ?? 'INSTALLMENT',
            'notes' => $validated['notes'] ?? 'Recorded via API.',
        ]);

        return response()->json([
            'success' => true,
            'payment' => $payment,
            'message' => 'Payment recorded and awaiting verification.',
        ], 201);
    }
}
