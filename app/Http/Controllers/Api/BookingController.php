<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hall;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Booking::with(['hall.venue', 'package', 'payments']);

        if ($user && !$user->isSuperAdmin() && $user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($user && $user->isCustomer()) {
            $query->where('customer_id', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('hall_id')) {
            $query->where('hall_id', $request->hall_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('event_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('event_date', '<=', $request->date_to);
        }

        $bookings = $query->latest()->paginate(20);

        return response()->json($bookings);
    }

    public function show(Request $request, string $id)
    {
        $user = Auth::user();

        $query = Booking::with(['hall.venue', 'package', 'payments', 'rsvps', 'kitchenKot', 'floorPlan']);

        if ($user && !$user->isSuperAdmin() && $user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($user && $user->isCustomer()) {
            $query->where('customer_id', $user->id);
        }

        $booking = $query->findOrFail($id);

        return response()->json($booking);
    }

    public function store(Request $request)
    {
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
            'per_head_rate' => 'nullable|numeric|min:0',
            'hall_rent' => 'nullable|numeric|min:0',
            'addons_total' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $hall = Hall::findOrFail($request->hall_id);

        if ($user && !$user->isSuperAdmin() && $hall->tenant_id !== $user->tenant_id) {
            return response()->json(['message' => 'Unauthorized access to this hall.'], 403);
        }

        $data = $request->all();
        $data['tenant_id'] = $hall->tenant_id;
        $data['created_by_user_id'] = $user?->id;
        $data['customer_id'] = $user?->id;

        if ($user && $user->isCustomer()) {
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
        } catch (\App\Exceptions\SlotAlreadyBookedException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        $query = Booking::where('id', $id);

        if ($user && !$user->isSuperAdmin() && $user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $booking = $query->firstOrFail();

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:INQUIRY,HOLD,CONFIRMED,COMPLETED,CANCELLED,REFUNDED',
            'notes' => 'sometimes|string|max:1000',
            'advance_paid' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $booking->update($request->only(['status', 'notes', 'advance_paid']));

        if ($request->has('advance_paid')) {
            $booking->updatePaymentBalance();
        }

        return response()->json([
            'success' => true,
            'booking' => $booking->fresh(),
            'message' => 'Booking updated successfully.',
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $user = Auth::user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Only super admin can delete bookings.'], 403);
        }

        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json([
            'success' => true,
            'message' => 'Booking deleted successfully.',
        ]);
    }

    public function availability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hall_id' => 'required|uuid|exists:halls,id',
            'date' => 'required|date',
            'slot_type' => 'sometimes|in:AFTERNOON,EVENING,NIGHT,FULL_DAY',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hall = Hall::with(['venue', 'tenant'])->findOrFail($request->hall_id);

        if (!$hall->tenant || !$hall->tenant->is_active || $hall->tenant->verification_status !== 'APPROVED') {
            return response()->json(['message' => 'Hall is not available for booking.'], 403);
        }

        $date = \Carbon\Carbon::parse($request->date);
        $slotType = $request->slot_type;

        $bookings = Booking::where('hall_id', $hall->id)
            ->whereDate('event_date', $date)
            ->whereIn('status', ['HOLD', 'CONFIRMED'])
            ->get();

        $bookedSlots = [];
        foreach ($bookings as $booking) {
            $slots = $booking->slot_type === 'FULL_DAY'
                ? ['AFTERNOON', 'EVENING', 'NIGHT']
                : [$booking->slot_type];
            foreach ($slots as $slot) {
                $bookedSlots[] = $slot;
            }
        }

        $allSlots = ['AFTERNOON', 'EVENING', 'NIGHT', 'FULL_DAY'];
        $availableSlots = array_diff($allSlots, $bookedSlots);

        if ($slotType && !in_array($slotType, $availableSlots)) {
            return response()->json([
                'available' => false,
                'message' => 'Selected slot is already booked.',
                'available_slots' => $availableSlots,
            ]);
        }

        return response()->json([
            'available' => true,
            'hall' => $hall,
            'date' => $date->format('Y-m-d'),
            'available_slots' => $availableSlots,
            'booked_slots' => $bookedSlots,
        ]);
    }
}