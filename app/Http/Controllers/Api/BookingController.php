<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\SlotAlreadyBookedException;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Package;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Booking API (mobile / partner integrations).
 *
 * Authorization is fail-closed: an unauthenticated caller receives 401 and a
 * tenant staff member can only ever reach their own tenant's bookings.
 */
class BookingController extends ApiController
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request)
    {
        $user = $this->apiUser($request);

        $query = Booking::with(['hall.venue', 'package', 'payments']);

        $query = $this->scopeToPrincipal($query, $user);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('hall_id')) {
            $query->where('hall_id', $request->string('hall_id')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('event_date', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('event_date', '<=', $request->string('date_to')->toString());
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function show(Request $request, string $id)
    {
        $user = $this->apiUser($request);

        $query = Booking::with(['hall.venue', 'package', 'payments', 'rsvps', 'kitchenKot', 'floorPlan']);

        $query = $this->scopeToPrincipal($query, $user);

        $booking = $query->findOrFail($id);

        return response()->json($booking);
    }

    public function store(Request $request)
    {
        $user = $this->apiUser($request);

        $this->assertPermission($user, 'bookings.create');

        $validator = Validator::make($request->all(), [
            'hall_id' => 'required|uuid|exists:halls,id',
            'event_type' => 'required|string|max:50',
            'event_date' => 'required|date|after:today',
            'slot_type' => 'required|in:AFTERNOON,EVENING,NIGHT,FULL_DAY',
            'guest_count' => 'required|integer|min:1|max:10000',
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'required|string|max:25',
            'customer_email' => 'nullable|email|max:100',
            'customer_cnic' => 'nullable|string|max:20',
            'package_id' => 'nullable|uuid|exists:packages,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($user->isCustomer()) {
            // Customers may only reserve halls that are currently listed on
            // the public marketplace; knowing a hidden hall UUID is not access.
            $hall = Hall::query()
                ->publicVisible()
                ->whereKey($request->hall_id)
                ->with('tenant')
                ->firstOrFail();
        } else {
            $hall = Hall::with('tenant')->findOrFail($request->hall_id);
            $this->assertTenantAccess($hall->tenant_id, $user);
        }

        // Every principal is subject to the same subscription and venue
        // entitlement check before a reservation can be created.
        abort_unless($hall->tenant && $hall->tenant->canAcceptBookings(), 403, 'Venue is not accepting bookings.');

        // NEVER pass raw request input into the booking service: financial
        // fields, lifecycle status, references and payment records must be
        // derived server-side from authoritative records.
        $data = $validator->validated();

        $package = null;
        if (! empty($data['package_id'])) {
            $package = Package::query()
                ->whereKey($data['package_id'])
                ->where('tenant_id', $hall->tenant_id)
                ->where('is_active', true)
                ->first();

            if (! $package) {
                return response()->json([
                    'errors' => ['package_id' => ['The selected package is not available for this venue.']],
                ], 422);
            }
        }

        $data['tenant_id'] = $hall->tenant_id;
        $data['created_by_user_id'] = $user->id;
        $data['customer_id'] = $user->isCustomer() ? $user->id : null;

        // Prices come from the venue/package; discounts, taxes and advances
        // require a verified payment flow and are never caller-controlled.
        $data['per_head_rate'] = $package?->per_head_price ?? 0;
        $data['hall_rent'] = $hall->base_rent ?? 0;
        $data['addons_total'] = 0;
        $data['discount_amount'] = 0;
        $data['tax_amount'] = 0;
        $data['advance_paid'] = 0;

        // New API bookings start as inquiries; staff confirm them after the
        // venue verifies availability and payment.
        $data['status'] = 'INQUIRY';
        $data['pipeline_stage'] = 'NEW_INQUIRY';

        if ($user->isCustomer()) {
            $data['customer_name'] = $user->name;
            $data['customer_phone'] = $user->phone;
            $data['customer_email'] = $user->email;
        }

        try {
            $booking = $this->bookingService->createReservation($data);

            return response()->json([
                'success' => true,
                'booking' => $booking,
                'message' => 'Booking created successfully.',
            ], 201);
        } catch (SlotAlreadyBookedException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking.',
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $user = $this->apiUser($request);

        $this->assertPermission($user, 'bookings.update');

        $booking = Booking::where('id', $id)
            ->where(function ($query) use ($user) {
                if ($user->isSuperAdmin()) {
                    return $query;
                }

                if ($user->isCustomer()) {
                    return $query->where('customer_id', $user->id);
                }

                return $query->where('tenant_id', $user->tenant_id);
            })
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            // Mirror the bookings.status DB enum exactly: REFUNDED is NOT a
            // valid column value and would fail on MySQL strict enums.
            'status' => 'sometimes|in:INQUIRY,HOLD,CONFIRMED,COMPLETED,CANCELLED',
            'notes' => 'sometimes|string|max:1000',
            // Money is never writable through this endpoint. Cash receipts must
            // go through PaymentService, which enforces cash-only methods,
            // ownership, idempotency and a tamper-evident audit trail.
            'advance_paid' => 'prohibited',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Customers may only annotate their own booking; they can never move
        // financial or lifecycle state through the API.
        if ($user->isCustomer()) {
            abort_unless($request->hasOnly(['notes']), 403, 'Customers may only update booking notes.');
        }

        $booking->update($request->only(['status', 'notes']));

        return response()->json([
            'success' => true,
            'booking' => $booking->fresh(),
            'message' => 'Booking updated successfully.',
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $user = $this->apiUser($request);

        abort_unless($user->isSuperAdmin(), 403, 'Only super admin can delete bookings.');

        $booking = Booking::withCount(['payments', 'rsvps', 'floorPlan', 'kitchenKot', 'utilityLog'])
            ->findOrFail($id);

        // Only empty inquiry/cancelled rows may be hard-deleted. Every
        // confirmed/operational/financial record is retained as a cancellation.
        $hasHistory = $booking->payments_count > 0
            || $booking->rsvps_count > 0
            || $booking->floor_plan_count > 0
            || $booking->kitchen_kot_count > 0
            || $booking->utility_log_count > 0;

        if ($hasHistory || ! in_array($booking->status, ['INQUIRY', 'CANCELLED'], true)) {
            $booking->update(['status' => 'CANCELLED']);

            return response()->json([
                'success' => true,
                'archived' => true,
                'message' => 'Booking has financial or operational history and was archived (cancelled) instead of deleted.',
            ]);
        }

        $booking->delete();

        return response()->json([
            'success' => true,
            'message' => 'Booking deleted successfully.',
        ]);
    }

    public function availability(Request $request)
    {
        $user = $this->apiUser($request);

        $validator = Validator::make($request->all(), [
            'hall_id' => 'required|uuid|exists:halls,id',
            'date' => 'required|date',
            'slot_type' => 'sometimes|in:AFTERNOON,EVENING,NIGHT,FULL_DAY',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hall = Hall::with(['venue', 'tenant'])->findOrFail($request->hall_id);

        if ($user->isCustomer()) {
            abort_unless(
                Hall::query()
                    ->publicVisible()
                    ->whereKey($hall->id)
                    ->exists(),
                404,
                'Venue is not publicly available.'
            );
        } else {
            $this->assertTenantAccess($hall->tenant_id, $user);
        }

        if (! $hall->tenant || ! $hall->tenant->canAcceptBookings()) {
            return response()->json(['message' => 'Venue is not accepting bookings.'], 403);
        }

        $slotType = $request->input('slot_type', 'FULL_DAY');

        return response()->json([
            'hall_id' => $hall->id,
            'date' => $request->string('date')->toString(),
            'slot_type' => $slotType,
            'available' => $this->bookingService->checkSlotAvailability($hall->id, $request->string('date')->toString(), $slotType),
        ]);
    }
}
