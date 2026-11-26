<?php

namespace App\Services;

use App\Events\BookingCreatedEvent;
use App\Exceptions\SlotAlreadyBookedException;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Tenant;
use App\Support\CashOnlyPayments;
use App\Support\PermissionMatrix;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookingService
{
    protected KitchenOrderService $kitchenOrderService;

    protected HallAvailabilityService $availabilityService;

    protected PaymentService $paymentService;

    public function __construct(
        KitchenOrderService $kitchenOrderService,
        HallAvailabilityService $availabilityService,
        PaymentService $paymentService
    ) {
        $this->kitchenOrderService = $kitchenOrderService;
        $this->availabilityService = $availabilityService;
        $this->paymentService = $paymentService;
    }

    /**
     * Check if a specific hall slot is available on a given date.
     */
    public function checkSlotAvailability(string $hallId, string $eventDate, string $slotType): bool
    {
        return $this->availabilityService->isSlotAvailable($hallId, $eventDate, $slotType);
    }

    /**
     * Create an atomic, concurrency-safe reservation with Redis/Cache lock and DB transaction row lock.
     *
     * @throws SlotAlreadyBookedException
     */
    public function createReservation(array $data): Booking
    {
        $hallId = $data['hall_id'] ?? null;

        if (! is_string($hallId) || $hallId === '') {
            throw new \InvalidArgumentException('A valid hall is required for a reservation.');
        }

        $eventDate = Carbon::parse($data['event_date'])->format('Y-m-d');
        $slotType = strtoupper((string) $data['slot_type']);

        if (Carbon::parse($eventDate)->isBefore(Carbon::today())) {
            throw new \InvalidArgumentException('The event date cannot be in the past.');
        }

        if (! in_array($slotType, ['AFTERNOON', 'EVENING', 'NIGHT', 'FULL_DAY'], true)) {
            throw new \InvalidArgumentException('Unsupported booking slot.');
        }

        // The hall record is the only authority for tenant ownership. Resolve it
        // before trusting any caller-controlled financial or lifecycle value.
        $hall = Hall::with(['tenant', 'venue'])->findOrFail($hallId);

        if (
            ! $hall->is_active
            || ! $hall->venue?->is_active
            || ! $hall->tenant
            || ! $hall->tenant->canAcceptBookings()
        ) {
            throw new \InvalidArgumentException('Venue is not accepting bookings.');
        }

        $data = $this->authorizeReservationInput($data, $hall);

        // Serialize every reservation decision for this hall/date. A date-level lock is
        // required because FULL_DAY conflicts with all three individual shifts. The
        // store is configurable (BOOKING_LOCK_STORE) and defaults to the shared
        // database store so the mutex is honoured by every application instance.
        $lockKey = "lock:hall:{$hallId}:date:{$eventDate}";
        $lock = Cache::store(config('booking.lock_store'))->lock($lockKey, 600);

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
            $booking = DB::transaction(function () use ($data, $hall, $hallId, $eventDate, $slotType) {
                // Recheck subscription and hall state under row locks. This
                // serializes a reservation against suspension, expiry and
                // hall archival that may happen after the initial page load.
                $tenant = Tenant::query()
                    ->whereKey($hall->tenant_id)
                    ->lockForUpdate()
                    ->first();
                $lockedHall = Hall::with(['tenant', 'venue'])
                    ->whereKey($hallId)
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $tenant
                    || ! $lockedHall
                    || $lockedHall->tenant_id !== $tenant->id
                    || ! $lockedHall->is_active
                    || ! $lockedHall->venue?->is_active
                    || ! $tenant->canAcceptBookings()
                ) {
                    throw new \InvalidArgumentException('Venue is not accepting bookings.');
                }

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

                $tenantId = $lockedHall->tenant_id;

                // Values below were normalized and validated before the lock was
                // taken; the transaction only persists the authoritative set.
                $guestCount = (int) $data['guest_count'];
                $perHeadRate = (float) $data['per_head_rate'];
                $hallRent = (float) $data['hall_rent'];
                $addonsTotal = (float) $data['addons_total'];
                $discountAmount = (float) $data['discount_amount'];
                $taxAmount = (float) $data['tax_amount'];
                $grandTotal = (float) $data['grand_total'];
                $advancePaid = (float) $data['advance_paid'];
                $balanceDue = (float) $data['balance_due'];

                // Create the Booking record
                $booking = Booking::create([
                    'tenant_id' => $tenantId,
                    'hall_id' => $hallId,
                    'package_id' => $data['package_id'],
                    'booking_reference' => $data['booking_reference'],
                    'created_by_user_id' => $data['created_by_user_id'],
                    'customer_id' => $data['customer_id'],
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
                    'status' => $data['status'],
                    'hold_expires_at' => $this->resolveHoldExpiry($lockedHall, $data),
                    'pipeline_stage' => $data['pipeline_stage'],
                    'notes' => $data['notes'] ?? null,
                ]);

                // Create token advance payment if advance paid > 0
                if ($advancePaid > 0) {
                    Payment::create([
                        'tenant_id' => $tenantId,
                        'booking_id' => $booking->id,
                        'amount' => $advancePaid,
                        'payment_type' => 'TOKEN_ADVANCE',
                        // Cash-only: new payments are always CASH, regardless of
                        // any caller-supplied method label.
                        'payment_method' => $data['payment_method'],
                        'receipt_no' => Payment::generateReceiptNumber(),
                        'transaction_reference' => $data['transaction_reference'] ?? null,
                        'received_by_user_id' => $data['created_by_user_id'],
                        'notes' => 'Token advance deposit collected at front desk upon reservation.',
                    ]);
                }

                // KOT authority is single-sourced in KitchenOrderService: it
                // only creates tickets for CONFIRMED bookings and never rewinds
                // an existing ticket's preparation state.
                $this->kitchenOrderService->generateKotForConfirmedBooking($booking);

                // HOLD/CONFIRMED reservations still notify listeners; a public
                // INQUIRY is only a request and must not fire booking events.
                if (strtoupper((string) $booking->status) !== 'INQUIRY') {
                    event(new BookingCreatedEvent($booking));
                }

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
     * Normalize and validate every caller-controlled value before it can reach
     * the bookings or payments tables.
     *
     * Web and API callers derive rates from authoritative hall/package records,
     * but the service boundary itself must still reject forged references,
     * negative amounts, overpayment, cross-tenant packages, arbitrary lifecycle
     * states and customer-ID spoofing.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function authorizeReservationInput(array $data, Hall $hall): array
    {
        $packageId = $data['package_id'] ?? null;

        if ($packageId !== null && $packageId !== '') {
            $package = Package::query()
                ->whereKey($packageId)
                ->where('tenant_id', $hall->tenant_id)
                ->where('is_active', true)
                ->first();

            if (! $package) {
                throw new \InvalidArgumentException('The selected package is not available for this venue.');
            }
        } else {
            $packageId = null;
        }

        // Customer identity may only come from the authenticated principal.
        $authenticated = auth()->user();
        $customerId = null;

        if ($authenticated && $authenticated->isCustomer()) {
            $suppliedCustomerId = $data['customer_id'] ?? null;

            if ($suppliedCustomerId !== null && (string) $suppliedCustomerId !== (string) $authenticated->id) {
                throw new \InvalidArgumentException('A customer may not create a booking for another account.');
            }

            $customerId = $authenticated->id;
        }

        // When a principal is present it is the only valid creator; console
        // callers and fixtures may still supply an explicit staff ID.
        $createdBy = $authenticated?->id
            ?? (app()->runningInConsole() ? ($data['created_by_user_id'] ?? null) : null);

        if ($createdBy !== null && ! is_numeric($createdBy)) {
            throw new \InvalidArgumentException('Invalid booking creator.');
        }

        $defaultStatus = $authenticated?->isCustomer()
            || (! $authenticated && ! app()->runningInConsole())
            ? 'INQUIRY'
            : 'CONFIRMED';
        $status = strtoupper((string) ($data['status'] ?? $defaultStatus));

        if (! in_array($status, ['INQUIRY', 'HOLD', 'CONFIRMED'], true)) {
            throw new \InvalidArgumentException('Unsupported booking lifecycle state at creation.');
        }

        if ((! $authenticated && ! app()->runningInConsole()) || $authenticated?->isCustomer()) {
            if ($status !== 'INQUIRY') {
                throw new \Illuminate\Auth\Access\AuthorizationException('Public customers may only submit booking inquiries.');
            }
        } elseif ($authenticated && ! $authenticated->isSuperAdmin()) {
            if (! PermissionMatrix::userCan($authenticated, 'bookings.create')) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Booking creation permission is required.');
            }
        }

        $guestCount = (int) ($data['guest_count'] ?? 100);

        if ($guestCount < 1) {
            throw new \InvalidArgumentException('Guest count must be at least one.');
        }

        if ($hall->max_capacity && $guestCount > (int) $hall->max_capacity) {
            throw new \InvalidArgumentException(
                "The guest count ({$guestCount}) exceeds the maximum hall capacity of {$hall->max_capacity}."
            );
        }

        $money = [];

        foreach (['per_head_rate', 'hall_rent', 'addons_total', 'discount_amount', 'tax_amount', 'advance_paid'] as $field) {
            $value = (float) ($data[$field] ?? 0);

            if (! is_finite($value) || $value < 0) {
                throw new \InvalidArgumentException('Booking financial values must be non-negative numbers.');
            }

            $money[$field] = round($value, 2);
        }

        $subtotal = ($money['per_head_rate'] * $guestCount)
            + $money['hall_rent']
            + $money['addons_total'];

        if ($money['discount_amount'] > $subtotal + 0.009) {
            throw new \InvalidArgumentException('Discount cannot exceed the booking subtotal.');
        }

        $grandTotal = round(max(0, $subtotal - $money['discount_amount'] + $money['tax_amount']), 2);

        if ($money['advance_paid'] > $grandTotal + 0.009) {
            throw new \InvalidArgumentException('Cash advance cannot exceed the booking total.');
        }

        return array_merge($data, [
            'tenant_id' => $hall->tenant_id,
            'package_id' => $packageId,
            'customer_id' => $customerId,
            'created_by_user_id' => $createdBy,
            // Never trust a caller-supplied reference; it is a financial join key.
            'booking_reference' => Booking::generateReference(),
            'status' => $status,
            'pipeline_stage' => $status === 'INQUIRY' ? 'NEW_INQUIRY' : 'CONFIRMED',
            'guest_count' => $guestCount,
            'per_head_rate' => $money['per_head_rate'],
            'hall_rent' => $money['hall_rent'],
            'addons_total' => $money['addons_total'],
            'discount_amount' => $money['discount_amount'],
            'tax_amount' => $money['tax_amount'],
            'advance_paid' => $money['advance_paid'],
            'grand_total' => $grandTotal,
            'balance_due' => round(max(0, $grandTotal - $money['advance_paid']), 2),
            'payment_method' => CashOnlyPayments::assert(
                $data['payment_method'] ?? CashOnlyPayments::CASH,
                'booking payments'
            ),
        ]);
    }

    /**
     * Build the authoritative overlap query for public and staff reservations.
     * A FULL_DAY booking occupies every shift, while a new FULL_DAY booking
     * conflicts with any active individual shift on that date.
     */
    private function slotConflictQuery(string $hallId, string $eventDate, string $slotType)
    {
        // Delegated to the read-only authority so public availability displays
        // and the transactional write check use identical conflict rules.
        // The caller still applies lockForUpdate() inside the reservation txn.
        return $this->availabilityService->conflictQuery($hallId, $eventDate, $slotType);
    }

    /**
     * A HOLD receives an expiry only when the venue has configured hold hours
     * or the caller supplied an explicit timestamp. Nothing is invented otherwise.
     */
    private function resolveHoldExpiry(Hall $hall, array $data): mixed
    {
        if (strtoupper((string) ($data['status'] ?? 'CONFIRMED')) !== 'HOLD') {
            return null;
        }

        if (! empty($data['hold_expires_at'])) {
            return Carbon::parse($data['hold_expires_at']);
        }

        $hours = (int) ($hall->tenant?->hold_expiry_hours ?? 0);

        return $hours > 0 ? now()->addHours($hours) : null;
    }

    /**
     * Change a booking lifecycle state under the same hall/date mutex used by
     * reservation creation. HOLD and CONFIRMED transitions must re-check every
     * overlapping active reservation before the status can become blocking.
     *
     * @throws SlotAlreadyBookedException
     */
    public function transitionStatus(Booking $booking, string $status): Booking
    {
        $status = strtoupper(trim($status));

        if (! in_array($status, ['INQUIRY', 'HOLD', 'CONFIRMED', 'COMPLETED', 'CANCELLED'], true)) {
            throw new \InvalidArgumentException('Unsupported booking lifecycle state.');
        }

        $snapshot = $booking->fresh();
        if (! $snapshot) {
            throw new \InvalidArgumentException('Booking no longer exists.');
        }

        $hall = Hall::with(['tenant', 'venue'])->findOrFail($snapshot->hall_id);
        $eventDate = Carbon::parse($snapshot->event_date)->format('Y-m-d');
        $slotType = strtoupper((string) $snapshot->slot_type);

        if (in_array($status, ['HOLD', 'CONFIRMED'], true) && Carbon::parse($eventDate)->isBefore(Carbon::today())) {
            throw new \InvalidArgumentException('The event date cannot be in the past.');
        }

        $lockKey = "lock:hall:{$hall->id}:date:{$eventDate}";
        $lock = Cache::store(config('booking.lock_store'))->lock($lockKey, 600);

        try {
            $lock->block(5);
        } catch (LockTimeoutException $e) {
            throw new SlotAlreadyBookedException(
                'A concurrent reservation is currently being updated for this hall and date. Please try again momentarily.',
                423,
                (string) $hall->id,
                $eventDate,
                $slotType,
                $e
            );
        }

        try {
            return DB::transaction(function () use ($snapshot, $hall, $eventDate, $slotType, $status): Booking {
                // Keep the same lock order as createReservation(): tenant,
                // hall, booking. A concurrent suspension/archive cannot race
                // a confirmation into a venue that is no longer bookable.
                $tenant = Tenant::query()
                    ->whereKey($hall->tenant_id)
                    ->lockForUpdate()
                    ->first();
                $lockedHall = Hall::with(['tenant', 'venue'])
                    ->whereKey($hall->id)
                    ->lockForUpdate()
                    ->first();

                if (in_array($status, ['HOLD', 'CONFIRMED'], true) && (
                    ! $tenant
                    || ! $lockedHall
                    || (string) $lockedHall->tenant_id !== (string) $tenant->id
                    || ! $lockedHall->is_active
                    || ! $lockedHall->venue?->is_active
                    || ! $tenant->canAcceptBookings()
                )) {
                    throw new \InvalidArgumentException('Venue is not accepting bookings.');
                }

                $lockedBooking = Booking::query()
                    ->whereKey($snapshot->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array($status, ['HOLD', 'CONFIRMED'], true)) {
                    $conflict = $this->slotConflictQuery((string) $lockedHall->id, $eventDate, $slotType)
                        ->where('id', '!=', $lockedBooking->id)
                        ->lockForUpdate()
                        ->first();

                    if ($conflict) {
                        throw new SlotAlreadyBookedException(
                            "The slot [{$slotType}] on [{$eventDate}] for this hall is already booked (#{$conflict->id}).",
                            409,
                            (string) $hall->id,
                            $eventDate,
                            $slotType
                        );
                    }
                }

                $previousStatus = strtoupper((string) $lockedBooking->status);
                $updates = ['status' => $status];

                if ($status === 'HOLD') {
                    $updates['hold_expires_at'] = $this->resolveHoldExpiry($lockedHall ?? $hall, [
                        'status' => 'HOLD',
                        'hold_expires_at' => $lockedBooking->hold_expires_at,
                    ]);
                } else {
                    $updates['hold_expires_at'] = null;
                }

                if ($status === 'CONFIRMED') {
                    $updates['pipeline_stage'] = 'CONFIRMED';
                } elseif ($status === 'INQUIRY') {
                    $updates['pipeline_stage'] = 'NEW_INQUIRY';
                }

                $lockedBooking->update($updates);

                if ($status === 'CONFIRMED' && $previousStatus !== 'CONFIRMED') {
                    $confirmed = $lockedBooking->fresh();
                    $this->kitchenOrderService->generateKotForConfirmedBooking($confirmed);
                    event(new BookingCreatedEvent($confirmed));
                }

                return $lockedBooking->fresh();
            });
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Record a milestone payment (Second Installment, Final Settlement, Security Deposit).
     *
     * This is a thin compatibility wrapper around PaymentService so there is a
     * single authoritative cash-payment ledger path. The service rejects
     * customer actors, unsupported payment types, non-positive amounts and
     * cumulative overpayment.
     */
    public function recordMilestonePayment(Booking $booking, array $paymentData): Payment
    {
        $receivedBy = auth()->user();

        if (! $receivedBy) {
            throw new \RuntimeException('An authenticated staff member is required to record a payment.');
        }

        return $this->paymentService->recordCashPayment(
            $booking,
            (float) ($paymentData['amount'] ?? 0),
            strtoupper((string) ($paymentData['payment_type'] ?? 'SECOND_INSTALLMENT')),
            $receivedBy,
            $paymentData['transaction_reference'] ?? null,
            $paymentData['notes'] ?? null,
        );
    }
}
