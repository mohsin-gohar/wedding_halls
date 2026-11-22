<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ServiceBookingException;
use App\Models\ServiceBooking;
use App\Services\ServiceBookingService;
use App\Support\CashOnlyPayments;
use Illuminate\Http\Request;

/**
 * Service booking API (photography, catering, decoration, beauty, transport…).
 *
 * Scoped exactly like the hall booking API: tenant staff see their own
 * tenant, customers see their own bookings, nobody sees anything else.
 */
class ServiceBookingController extends ApiController
{
    public function __construct(protected ServiceBookingService $serviceBookingService) {}

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
     * Customers may only SUBMIT an unverified manual payment for their own
     * booking. Only authorized staff can verify it and change the balance.
     * Online/gateway method labels are not accepted.
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
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'method' => CashOnlyPayments::validationRule(),
            'kind' => ['nullable', 'in:ADVANCE,INSTALLMENT,FULL'],
            'reference' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $result = $this->serviceBookingService->submitCustomerPayment(
                $booking,
                $validated,
                (int) $booking->customer_id
            );
        } catch (\PDOException $exception) {
            // Never leak SQL/driver details to an API caller.
            report($exception);

            return response()->json(['message' => 'The payment could not be recorded. Please try again.'], 500);
        } catch (ServiceBookingException|\InvalidArgumentException|\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'payment' => $result['payment'],
            'replayed' => (bool) ($result['replayed'] ?? false),
            'message' => ! empty($result['replayed'])
                ? 'Payment already submitted; returning the original record.'
                : 'Payment recorded and awaiting verification.',
        ], ! empty($result['replayed']) ? 200 : 201);
    }
}
