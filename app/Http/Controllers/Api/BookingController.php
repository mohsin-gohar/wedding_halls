<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\StaffService;
use App\Services\NotificationService;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    protected $bookingService;
    protected $paymentService;
    protected $staffService;
    protected $notificationService;

    public function __construct(
        BookingService $bookingService,
        PaymentService $paymentService,
        StaffService $staffService,
        NotificationService $notificationService
    ) {
        $this->bookingService = $bookingService;
        $this->paymentService = $paymentService;
        $this->staffService = $staffService;
        $this->notificationService = $notificationService;
    }

    /** * Get available shifts for a venue and date * * GET /api/availability?venue={venue_id}&date={YYYY-mm-dd} * @return JsonResponse */
    public function availability(Request $request): JsonResponse
    {
        $venueId = $request->query('venue');
        $date = $request->query('date');

        if (!$venueId || !$date) {
            return response()->json([
                'success' => false,
                'message' => 'Venue ID and date are required',
            ], 400);
        }

        $shifts = $this->bookingService->getAvailableShifts($venueId, $date);

        return response()->json([
            'success' => true,
            'data' => $shifts,
        ]);
    }

    /** * Create a new booking * * POST /api/bookings * @return JsonResponse */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'venue_id' => 'required|exists:venues,id',
            'event_date' => 'required|date|after_or_equal:today',
            'slot_type' => 'required|in:AFTERNOON,EVENING,NIGHT',
            'guests_count' => 'required|integer|min:1|max:500',
            'payment_method' => 'required|in:stripe,jazzcash,easypaisa,bank_transfer,cash',
            'guests_name' => 'nullable|string|max:100',
            'special_requests' => 'nullable|string|max:500',
        ]);

        $result = $this->bookingService->createBooking($data);

        if ($result['success']) {
            // Send notification
            // $this->notificationService->sendBookingConfirmation(
            //     $result['data'] instanceof Booking ? $result['data'] : null
            // );

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => $result['message'],
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], $result['code'] ?? 422);
    }

    /** * Update booking status (accept/reject) * * PUT /api/bookings/{booking}/status * @return JsonResponse */
    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:INQUIRY,HOLD,CONFIRMED,COMPLETED,CANCELLED',
        ]);

        $result = $this->bookingService->updateBookingStatus($booking, $data['status']);

        return response()->json($result);
    }

    /** * Calculate balance due for a booking * * GET /api/bookings/{booking}/balance * @return JsonResponse */
    public function balance(Booking $booking): JsonResponse
    {
        $result = $this->bookingService->calculateBookingBalance($booking);

        return response()->json($result);
    }

    /** * Cancel a booking * * DELETE/PUT /api/bookings/{booking}/cancel * @return JsonResponse */
    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $reason = $request->query('reason', '');

        $result = $this->bookingService->cancelBooking($booking, $reason);

        return response()->json($result);
    }

    /** * Get payment summary for a booking * * GET /api/bookings/{booking}/payments/summary * @return JsonResponse */
    public function paymentSummary(Booking $booking): JsonResponse
    {
        $result = $this->paymentService->getPaymentSummary($booking);

        return response()->json($result);
    }
}