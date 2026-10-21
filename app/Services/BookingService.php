<?php

namespace App\Services;

use App\Events\BookingCreatedEvent;
use App\Exceptions\SlotAlreadyBookedException;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookingService
{
    protected KitchenOrderService $kitchenOrderService;

    public function __construct(KitchenOrderService $kitchenOrderService)
    {
        $this->kitchenOrderService = $kitchenOrderService;
    }

    /**
     * Check if a specific hall slot is available on a given date.
     */
    public function checkSlotAvailability(string $hallId, string $eventDate, string $slotType): bool
    {
        return ! $this->slotConflictQuery($hallId, $eventDate, $slotType)->exists();
    }

    /**
     * Create an atomic, concurrency-safe reservation with Redis/Cache lock and DB transaction row lock.
     *
     * @throws SlotAlreadyBookedException
     */
    public function createReservation(array $data): Booking
    {
        $hallId = $data['hall_id'];
        $eventDate = Carbon::parse($data['event_date'])->format('Y-m-d');
        $slotType = strtoupper($data['slot_type']);

        // Serialize every reservation decision for this hall/date. A date-level lock is
        // required because FULL_DAY conflicts with all three individual shifts.
        $lockKey = "lock:hall:{$hallId}:date:{$eventDate}";
        $lock = Cache::lock($lockKey, 600);

        try {
            // Attempt to acquire lock; wait up to 5 seconds for mutex
            $lock->block(5);
        } catch (LockTimeoutException $e) {
            throw new SlotAlreadyBookedException(
                'A concurrent reservation is currently in progress for this slot. Please try again momentarily.',
                423,
                $hallId,
                $eventDate,
                $slotType
            );
        }

        try {
            $booking = DB::transaction(function () use ($data, $hallId, $eventDate, $slotType) {
                // Strict row-level lock check: Verify no active booking exists in DB
                $existing = $this->slotConflictQuery($hallId, $eventDate, $slotType)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    throw new SlotAlreadyBookedException(
                        "The slot [{$slotType}] on [{$eventDate}] for this hall is already booked (#{$existing->id}).",
                        409,
                        $hallId,
                        $eventDate,
                        $slotType
                    );
                }

                // Authoritative tenant resolution: always derive the tenant from
                // the hall record instead of trusting a caller-supplied tenant_id.
                // A stale or mismatched value would otherwise either violate the
                // bookings.tenant_id foreign key or file the booking under the
                // wrong tenant.
                $hall = Hall::findOrFail($hallId);
                $tenantId = $hall->tenant_id;

                // Financial Formulation
                $guestCount = (int) ($data['guest_count'] ?? 100);
                $perHeadRate = (float) ($data['per_head_rate'] ?? 0.00);
                $hallRent = (float) ($data['hall_rent'] ?? 0.00);
                $addonsTotal = (float) ($data['addons_total'] ?? 0.00);
                $discountAmount = (float) ($data['discount_amount'] ?? 0.00);
                $taxAmount = (float) ($data['tax_amount'] ?? 0.00);

                $cateringTotal = $perHeadRate * $guestCount;
                $grandTotal = max(0, ($cateringTotal + $hallRent + $addonsTotal - $discountAmount) + $taxAmount);
                $advancePaid = (float) ($data['advance_paid'] ?? 0.00);
                $balanceDue = max(0, $grandTotal - $advancePaid);

                // Create the Booking record
                $booking = Booking::create([
                    'tenant_id' => $tenantId,
                    'hall_id' => $hallId,
                    'package_id' => $data['package_id'] ?? null,
                    'booking_reference' => $data['booking_reference'] ?? Booking::generateReference(),
                    'created_by_user_id' => $data['created_by_user_id'] ?? auth()->id(),
                    'customer_id' => $data['customer_id'] ?? null,
                    'customer_name' => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'],
                    'customer_cnic' => $data['customer_cnic'] ?? null,
                    'customer_email' => $data['customer_email'] ?? null,
                    'event_type' => $data['event_type'] ?? 'Barat',
                    'event_date' => $eventDate,
                    'slot_type' => $slotType,
                    'guest_count' => $guestCount,
                    'per_head_rate' => $perHeadRate,
                    'hall_rent' => $hallRent,
                    'addons_total' => $addonsTotal,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $taxAmount,
                    'grand_total' => $grandTotal,
                    'advance_paid' => $advancePaid,
                    'balance_due' => $balanceDue,
                    'custom_menu_json' => $data['custom_menu_json'] ?? null,
                    'addons_json' => $data['addons_json'] ?? null,
                    'status' => $data['status'] ?? 'CONFIRMED',
                    'pipeline_stage' => $data['pipeline_stage'] ?? (($data['status'] ?? 'CONFIRMED') === 'INQUIRY' ? 'NEW_INQUIRY' : 'CONFIRMED'),
                    'notes' => $data['notes'] ?? null,
                ]);

                // Create token advance payment if advance paid > 0
                if ($advancePaid > 0) {
                    Payment::create([
                        'tenant_id' => $tenantId,
                        'booking_id' => $booking->id,
                        'amount' => $advancePaid,
                        'payment_type' => 'TOKEN_ADVANCE',
                        'payment_method' => $data['payment_method'] ?? 'CASH',
                        'receipt_no' => Payment::generateReceiptNumber(),
                        'transaction_reference' => $data['transaction_reference'] ?? null,
                        'received_by_user_id' => $data['created_by_user_id'] ?? auth()->id(),
                        'notes' => 'Token advance deposit collected at front desk upon reservation.',
                    ]);
                }

                // Automatically formulate and attach Kitchen Order Ticket (KOT)
                $this->kitchenOrderService->generateKotForBooking($booking);

                // Dispatch BookingCreatedEvent
                event(new BookingCreatedEvent($booking));

                return $booking;
            });

            return $booking;
        } catch (UniqueConstraintViolationException $e) {
            throw new SlotAlreadyBookedException(
                "The slot [{$slotType}] on [{$eventDate}] for this hall is already booked in the database.",
                409,
                $hallId,
                $eventDate,
                $slotType,
                $e
            );
        } finally {
            // Safely release the distributed lock
            optional($lock)->release();
        }
    }

    /**
     * Build the authoritative overlap query for public and staff reservations.
     * A FULL_DAY booking occupies every shift, while a new FULL_DAY booking
     * conflicts with any active individual shift on that date.
     */
    private function slotConflictQuery(string $hallId, string $eventDate, string $slotType)
    {
        $normalizedSlot = strtoupper($slotType);
        $conflictingSlots = $normalizedSlot === 'FULL_DAY'
            ? ['AFTERNOON', 'EVENING', 'NIGHT', 'FULL_DAY']
            : [$normalizedSlot, 'FULL_DAY'];

        return Booking::where('hall_id', $hallId)
            ->whereDate('event_date', $eventDate)
            ->whereIn('slot_type', $conflictingSlots)
            ->whereIn('status', ['HOLD', 'CONFIRMED']);
    }

    /**
     * Record a milestone payment (Second Installment, Final Settlement, Security Deposit).
     */
    public function recordMilestonePayment(Booking $booking, array $paymentData): Payment
    {
        return DB::transaction(function () use ($booking, $paymentData) {
            $amount = (float) $paymentData['amount'];

            $payment = Payment::create([
                'tenant_id' => $booking->tenant_id,
                'booking_id' => $booking->id,
                'amount' => $amount,
                'payment_type' => $paymentData['payment_type'] ?? 'SECOND_INSTALLMENT',
                'payment_method' => $paymentData['payment_method'] ?? 'CASH',
                'receipt_no' => Payment::generateReceiptNumber(),
                'transaction_reference' => $paymentData['transaction_reference'] ?? null,
                'received_by_user_id' => auth()->id(),
                'notes' => $paymentData['notes'] ?? null,
            ]);

            $booking->updatePaymentBalance();

            return $payment;
        });
    }
}
