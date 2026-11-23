<?php

namespace App\Services;

use App\Events\ServiceBookingCreatedEvent;
use App\Exceptions\ServiceBookingException;
use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Models\RFQ;
use App\Models\RFQBid;
use App\Models\ServiceBooking;
use App\Models\ServicePayment;
use App\Models\Tenant;
use App\Models\VendorPackage;
use App\Models\VendorPayout;
use App\Models\VendorService;
use App\Models\VendorWallet;
use App\Models\WalletTransaction;
use App\Support\CashOnlyPayments;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Multi-vendor service booking orchestration (Phase 3).
 *
 * Turns an awarded RFQ quotation (RFQBid) into a ServiceBooking, runs the
 * confirmation → payment → settlement lifecycle and keeps the vendor wallet
 * ledger + payout pipeline coherent. All money math is PKR decimal(12,2) and
 * derives commission/advance defaults from platform settings (DB-driven, not
 * hardcoded).
 */
class ServiceBookingService
{
    // ─────────────────────────────────────────────────────────────
    // Quote lifecycle (award / decline)
    // ─────────────────────────────────────────────────────────────

    /**
     * Award an accepted quotation: the RFQ moves to AWARDED, competing bids are
     * rejected, and an ACCEPTED ServiceBooking is created with full financial
     * formulation (quoted amount, platform commission, net vendor payout, due
     * advance).
     */
    public function acceptQuote(int $rfqBidId, ?int $customerId = null): ServiceBooking
    {
        // Discover the RFQ/tenant before locking. The lock order below matches
        // createDirectBooking() (tenant → booking rows) to avoid deadlocks.
        $bid = RFQBid::query()->with('rfq')->find($rfqBidId);

        if (! $bid?->rfq) {
            throw ServiceBookingException::notFound('The quotation record could not be found.');
        }

        $rfqId = (string) $bid->rfq_id;
        $tenantId = (string) $bid->rfq->tenant_id;

        return DB::transaction(function () use ($rfqBidId, $customerId, $rfqId, $tenantId) {
            // Serialize every award and direct booking for this vendor.
            $tenant = Tenant::query()->whereKey($tenantId)->lockForUpdate()->first();

            // Locking only the bid is not enough: two different bids on the
            // same RFQ can both see OPEN. The RFQ row is the award mutex.
            $rfq = RFQ::query()->whereKey($rfqId)->lockForUpdate()->first();
            $bid = RFQBid::query()->whereKey($rfqBidId)->lockForUpdate()->first();

            if (! $tenant || ! $rfq || ! $bid) {
                throw ServiceBookingException::notFound('The quotation record could not be found.');
            }

            if ($rfq->status !== 'OPEN') {
                throw ServiceBookingException::notAwardable('This quotation request has already been awarded or closed.');
            }

            if ($bid->status !== 'PENDING') {
                throw ServiceBookingException::notAwardable('This quotation has already been answered.');
            }

            $preferences = (array) ($rfq->preferences ?? []);
            $serviceId = $preferences['service_id'] ?? null;
            $mode = $tenant->vendorCategory?->booking_mode ?? 'DATE_EVENT';

            if (in_array($mode, ['APPOINTMENT', 'HOURLY'], true) && $rfq->event_date) {
                $eventDate = $rfq->event_date->format('Y-m-d');
                $startTime = $preferences['start_time'] ?? null;
                $hours = isset($preferences['hours']) && (float) $preferences['hours'] > 0
                    ? (float) $preferences['hours']
                    : null;

                $this->assertSlotFree($tenant, $mode, $eventDate, $startTime ?: null, $hours);
            }

            $bid->update(['status' => 'ACCEPTED']);

            RFQBid::query()
                ->where('rfq_id', $rfq->id)
                ->where('id', '!=', $bid->id)
                ->where('status', 'PENDING')
                ->update(['status' => 'REJECTED']);

            $rfq->update(['status' => 'AWARDED']);

            $financials = $this->calculateFinancials(
                (float) $bid->bid_amount,
                0.0,
                $tenant
            );

            $booking = ServiceBooking::create([
                'tenant_id' => $rfq->tenant_id,
                'vendor_category_id' => $rfq->vendor_category_id,
                'bookable_type' => $serviceId ? VendorService::class : null,
                'bookable_id' => $serviceId,
                'customer_id' => $customerId ?? $rfq->client_id,
                'rfq_bid_id' => $bid->id,
                'customer_event_id' => null,
                'booking_reference' => ServiceBooking::generateReference(),
                'event_name' => (string) ($preferences['service_name'] ?? $rfq->title),
                'event_date' => $rfq->event_date?->format('Y-m-d'),
                'start_time' => $preferences['start_time'] ?? null,
                'end_time' => $this->resolveEndTime($preferences),
                'hours' => isset($preferences['hours']) && (int) $preferences['hours'] > 0 ? (int) $preferences['hours'] : null,
                'guest_count' => $rfq->guest_count,
                'requirements' => $rfq->requirements,
                'requirements_json' => empty($preferences) ? null : $preferences,
                'quoted_amount' => round((float) $bid->bid_amount, 2),
                'discount_amount' => 0.0,
                'advance_amount' => $financials['advance_amount'],
                'paid_amount' => 0.0,
                'commission_amount' => $financials['commission_amount'],
                'net_vendor_amount' => $financials['net_vendor_amount'],
                'status' => 'ACCEPTED',
                'payment_status' => 'UNPAID',
            ]);

            event(new ServiceBookingCreatedEvent($booking));

            return $booking;
        });
    }

    /**
     * Vendor declines a quotation request — closes the RFQ and rejects every
     * still-pending offer so no stale bid can be awarded afterwards.
     */
    public function declineQuote(int $rfqId, ?int $vendorUserId = null): void
    {
        DB::transaction(function () use ($rfqId): void {
            $rfq = RFQ::query()->whereKey($rfqId)->lockForUpdate()->first();

            if (! $rfq) {
                throw ServiceBookingException::notFound('The quotation request could not be found.');
            }

            if ($rfq->status !== 'OPEN') {
                throw ServiceBookingException::invalidState('This quotation request is no longer open.');
            }

            $rfq->update(['status' => 'CLOSED']);

            RFQBid::query()
                ->where('rfq_id', $rfq->id)
                ->where('status', 'PENDING')
                ->update(['status' => 'REJECTED']);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Direct booking ("Book Now" — no RFQ round-trip)
    // ─────────────────────────────────────────────────────────────

    /**
     * Create a direct booking from a vendor's service/package card.
     *
     * Guarantees:
     *  - the price is resolved SERVER-SIDE from the bookable's own pricing
     *    fields (there is no price input, so a tampered amount is impossible);
     *  - the write runs in a transaction with the tenant row locked, so two
     *    concurrent bookings for the same slot cannot both succeed;
     *  - slot exclusivity per booking mode (APPOINTMENT: exact start_time;
     *    HOURLY: interval overlap) against every active booking of this vendor;
     *  - the booking starts as ACCEPTED — the vendor still confirms it
     *    through the existing bookings board (ACCEPTED → CONFIRMED).
     *
     * @param  array{event_name:string,event_date:string,start_time?:?string,hours?:?float,guest_count?:?int,requirements?:?string}  $attributes
     */
    public function createDirectBooking(
        Tenant $tenant,
        int $customerId,
        VendorService|VendorPackage $bookable,
        array $attributes
    ): ServiceBooking {
        $category = $tenant->vendorCategory;
        $mode = $category?->booking_mode ?? 'DATE_EVENT';
        $price = $this->resolveDirectPrice($bookable, $mode, $attributes);

        return DB::transaction(function () use ($tenant, $customerId, $bookable, $attributes, $mode, $price) {
            // Serialize every booking write for this vendor: the lock is held
            // for the rest of the transaction, so a concurrent direct booking
            // (or RFQ award) waits here and then sees our row.
            Tenant::query()->whereKey($tenant->id)->lockForUpdate()->first();

            $eventDate = (string) $attributes['event_date'];
            $startTime = $attributes['start_time'] ?? null;
            if ($startTime === '') {
                $startTime = null;
            }
            $hours = isset($attributes['hours']) && (float) $attributes['hours'] > 0
                ? (float) $attributes['hours']
                : null;

            if (in_array($mode, ['APPOINTMENT', 'HOURLY'], true)) {
                $this->assertSlotFree($tenant, $mode, $eventDate, $startTime, $hours);
            }

            $financials = $this->calculateFinancials($price, 0.0, $tenant);

            $endTime = $this->resolveEndTime(['start_time' => $startTime, 'hours' => $hours]);

            $booking = ServiceBooking::create([
                'tenant_id' => $tenant->id,
                'vendor_category_id' => $tenant->vendor_category_id,
                'bookable_type' => $bookable->getMorphClass(),
                'bookable_id' => $bookable->getKey(),
                'customer_id' => $customerId,
                'rfq_bid_id' => null,
                'customer_event_id' => null,
                'booking_reference' => ServiceBooking::generateReference(),
                'event_name' => (string) $attributes['event_name'],
                'event_date' => $eventDate,
                'start_time' => $startTime ?: null,
                'end_time' => $endTime ?: null,
                'hours' => $hours,
                'guest_count' => $attributes['guest_count'] ?? null,
                'requirements' => $attributes['requirements'] ?? null,
                'requirements_json' => [
                    'booking_mode' => $mode,
                    'bookable_label' => $bookable->name,
                    'source' => 'direct',
                ],
                'quoted_amount' => $financials['quoted_amount'],
                'discount_amount' => 0.0,
                'advance_amount' => $financials['advance_amount'],
                'paid_amount' => 0.0,
                'commission_amount' => $financials['commission_amount'],
                'net_vendor_amount' => $financials['net_vendor_amount'],
                'status' => 'ACCEPTED',
                'payment_status' => 'UNPAID',
            ]);

            event(new ServiceBookingCreatedEvent($booking));

            return $booking;
        });
    }

    /**
     * Non-throwing price preview for the booking form (null = not directly
     * bookable, i.e. "request a quote" is the only path for this item).
     *
     * @param  array{guest_count?:?int,hours?:?float}  $attributes
     */
    public function previewDirectPrice(
        VendorService|VendorPackage $bookable,
        string $mode,
        array $attributes = []
    ): ?float {
        try {
            return $this->resolveDirectPrice($bookable, $mode, $attributes);
        } catch (ServiceBookingException) {
            return null;
        }
    }

    /**
     * Server-side price resolution — packages are flat-priced; services are
     * per-head (× guests) or hourly (× hours) when the category says so.
     *
     * @param  array{guest_count?:?int,hours?:?float}  $attributes
     *
     * @throws ServiceBookingException when the item has no fixed price
     */
    protected function resolveDirectPrice(
        VendorService|VendorPackage $bookable,
        string $mode,
        array $attributes
    ): float {
        $base = $bookable instanceof VendorPackage
            ? ($bookable->price ?? $bookable->price_from)
            : ($bookable->price_from ?? $bookable->price_to);

        if ($base === null || (float) $base <= 0) {
            throw ServiceBookingException::notAwardable(
                'This item does not have a fixed price yet — please request a quotation instead.'
            );
        }

        $base = (float) $base;

        // Packages carry a package total regardless of mode.
        if ($bookable instanceof VendorPackage) {
            return round($base, 2);
        }

        return match ($mode) {
            'PER_HEAD' => round($base * $this->requirePositive($attributes['guest_count'] ?? null, 'guest count'), 2),
            'HOURLY' => round($base * $this->requirePositive($attributes['hours'] ?? null, 'hours'), 2),
            default => round($base, 2),
        };
    }

    /**
     * @throws ServiceBookingException
     */
    protected function requirePositive(mixed $value, string $label): float
    {
        $value = $value !== null ? (float) $value : 0.0;

        if ($value <= 0) {
            throw ServiceBookingException::invalidState(
                'A '.$label.' is required before this service can be booked directly.'
            );
        }

        return $value;
    }

    /**
     * Slot exclusivity for APPOINTMENT (exact start_time) and HOURLY
     * (interval overlap) modes, evaluated under the tenant row lock.
     *
     * @throws ServiceBookingException 409 when the slot is taken
     */
    protected function assertSlotFree(
        Tenant $tenant,
        string $mode,
        string $eventDate,
        ?string $startTime,
        ?float $hours
    ): void {
        if (! $startTime) {
            throw ServiceBookingException::invalidState('A start time is required for this booking.');
        }

        $existing = ServiceBooking::query()
            ->where('tenant_id', $tenant->id)
            ->where('event_date', $eventDate)
            ->whereIn('status', ['PENDING_VENDOR', 'QUOTE_SENT', 'ACCEPTED', 'CONFIRMED', 'IN_PROGRESS'])
            ->whereNotNull('start_time')
            ->get(['id', 'booking_reference', 'start_time', 'hours']);

        $start = Carbon::parse($startTime);

        foreach ($existing as $booking) {
            $existingStart = Carbon::parse($booking->start_time);

            if ($mode === 'APPOINTMENT') {
                if ($existingStart->format('H:i') === $start->format('H:i')) {
                    throw ServiceBookingException::invalidState(
                        'That time slot is already booked. Please pick another time.'
                    );
                }

                continue;
            }

            // HOURLY: interval overlap. Bookings without a stored duration
            // are treated as one hour so a bare start time still reserves it.
            $existingHours = max(1.0, (float) ($booking->hours ?? 0));
            $existingEnd = $existingStart->copy()->addMinutes((int) round($existingHours * 60));

            $newEnd = ($hours !== null && $hours > 0)
                ? $start->copy()->addMinutes((int) round($hours * 60))
                : $start->copy()->addMinute();

            if ($start->lt($existingEnd) && $existingStart->lt($newEnd)) {
                throw ServiceBookingException::invalidState(
                    'That time range overlaps an existing booking ('.$booking->booking_reference.'). Please pick another slot.'
                );
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Service booking lifecycle
    // ─────────────────────────────────────────────────────────────

    public function confirmServiceBooking(string $serviceBookingId, string $tenantId): ServiceBooking
    {
        $booking = $this->findTenantBooking($serviceBookingId, $tenantId);

        if ($booking->status !== 'ACCEPTED') {
            throw ServiceBookingException::invalidState('Only accepted service bookings can be confirmed.');
        }

        $booking->update(['status' => 'CONFIRMED', 'confirmed_at' => now()]);

        $financials = $this->calculateFinancials(
            (float) $booking->quoted_amount,
            (float) $booking->discount_amount,
            Tenant::find($booking->tenant_id)
        );

        $booking->update(['advance_amount' => $financials['advance_amount']]);

        return $booking->fresh();
    }

    public function startServiceBooking(string $serviceBookingId, string $tenantId): ServiceBooking
    {
        $booking = $this->findTenantBooking($serviceBookingId, $tenantId);

        if (! in_array($booking->status, ['ACCEPTED', 'CONFIRMED'], true)) {
            throw ServiceBookingException::invalidState('Only accepted or confirmed bookings can be started.');
        }

        $booking->update(['status' => 'IN_PROGRESS']);

        return $booking->fresh();
    }

    public function completeServiceBooking(string $serviceBookingId, string $tenantId): ServiceBooking
    {
        $booking = $this->findTenantBooking($serviceBookingId, $tenantId);

        if ($booking->status !== 'IN_PROGRESS') {
            throw ServiceBookingException::invalidState('Only in-progress service bookings can be marked completed.');
        }

        $booking->update(['status' => 'COMPLETED', 'completed_at' => now()]);

        return $booking->fresh();
    }

    public function cancelServiceBooking(string $serviceBookingId, ?string $reason, string $tenantId): ServiceBooking
    {
        // A refund touches the booking, a refund row and the wallet ledger. If
        // the wallet debit fails, none of those writes may survive.
        return DB::transaction(function () use ($serviceBookingId, $reason, $tenantId): ServiceBooking {
            $booking = $this->findTenantBooking($serviceBookingId, $tenantId);

            if (! $booking->isCancellable()) {
                throw ServiceBookingException::invalidState('This service booking can no longer be cancelled.');
            }

            $booking->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            if ((float) $booking->paid_amount > 0) {
                $refund = ServicePayment::create([
                    'tenant_id' => $booking->tenant_id,
                    'service_booking_id' => $booking->id,
                    'customer_id' => $booking->customer_id,
                    'amount' => (float) $booking->paid_amount,
                    'method' => CashOnlyPayments::CASH,
                    'status' => 'REFUNDED',
                    'kind' => 'REFUND',
                    'notes' => $reason ?? 'Service booking cancelled before completion.',
                ]);

                $this->reverseBookingPayment($booking, (float) $refund->amount);

                $booking->update(['paid_amount' => 0.0, 'payment_status' => 'REFUNDED']);
            }

            return $booking->fresh();
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Payments & wallet settlement
    // ─────────────────────────────────────────────────────────────

    /**
     * Record a service payment (e.g. cash collected on site) and apply it to the
     * booking balance. Settlement into the vendor wallet + payout pipeline runs
     * when the booking reaches a fully paid state.
     *
     * @internal Trusted server-side callers only. Customer-facing HTTP requests
     *           must use submitCustomerPayment() and staff verification.
     */
    public function recordServicePayment(ServiceBooking $booking, float $amount, string $method, string $kind = 'INSTALLMENT'): ServicePayment
    {
        CashOnlyPayments::assert($method, 'service payments');

        $actor = auth()->user();

        if ($actor && $actor->isCustomer()) {
            throw new ServiceBookingException('Only authorized staff may record a verified service payment.');
        }

        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Service payment amount must be greater than zero.');
        }

        $kind = strtoupper($kind);

        if (! in_array($kind, ['ADVANCE', 'INSTALLMENT', 'FULL'], true)) {
            throw new \InvalidArgumentException('Unsupported service payment kind.');
        }

        return DB::transaction(function () use ($booking, $amount, $method, $kind): ServicePayment {
            $booking = ServiceBooking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            // A verified write is still a financial mutation: it must obey the
            // same booking-state and remaining-balance rules as a staff
            // verification of a pending customer submission.
            if (! in_array($booking->status, ['ACCEPTED', 'CONFIRMED', 'IN_PROGRESS'], true)) {
                throw ServiceBookingException::invalidState('This service booking is not in a payable state.');
            }

            $balance = round($booking->balanceDue(), 2);

            if ($amount > $balance + 0.009) {
                throw ServiceBookingException::invalidState(
                    'Amount exceeds the outstanding balance of PKR '.number_format($balance, 2).'.'
                );
            }

            $payment = ServicePayment::create([
                'tenant_id' => $booking->tenant_id,
                'service_booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'amount' => $amount,
                'method' => $method,
                'status' => 'VERIFIED',
                'kind' => $kind,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'notes' => 'Service payment recorded by operations staff.',
            ]);

            $this->applyVerifiedPayment($booking, $payment);

            return $payment->fresh();
        });
    }

    /**
     * Customer submission of a manual cash payment. The record stays PENDING;
     * only staff verification changes the booking balance.
     *
     * The booking row is locked and the outstanding balance is re-read inside
     * the transaction, so two concurrent submissions cannot both consume the
     * same balance. A supplied reference is the idempotency key: replaying it
     * returns the canonical row instead of creating a duplicate.
     *
     * @param  array<string, mixed>  $data
     * @return array{success: bool, payment: ServicePayment, replayed?: bool}
     */
    public function submitCustomerPayment(ServiceBooking $booking, array $data, int $customerId): array
    {
        CashOnlyPayments::assert($data['method'] ?? CashOnlyPayments::CASH, 'service payments');

        $amount = round((float) ($data['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $reference = $this->normalizeSubmissionReference($data['reference'] ?? null);
        $kind = strtoupper((string) ($data['kind'] ?? 'INSTALLMENT'));

        if (! in_array($kind, ['ADVANCE', 'INSTALLMENT', 'FULL'], true)) {
            throw new \InvalidArgumentException('Unsupported service payment kind.');
        }

        return DB::transaction(function () use ($booking, $customerId, $amount, $reference, $kind, $data) {
            $locked = ServiceBooking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ((string) $locked->customer_id !== (string) $customerId) {
                throw new \RuntimeException('You can only pay for your own booking.');
            }

            if (in_array($locked->status, ['CANCELLED', 'DECLINED'], true)) {
                throw new \RuntimeException('This service booking is no longer accepting payments.');
            }

            if ($reference !== null) {
                $existing = ServicePayment::query()
                    ->where('service_booking_id', $locked->id)
                    ->where('reference', $reference)
                    ->first();

                if ($existing) {
                    $this->assertSubmissionReplayMatches($existing, $amount, $kind);

                    return ['success' => true, 'payment' => $existing, 'replayed' => true];
                }
            }

            // Verified payments already applied to the booking plus unverified
            // submissions awaiting staff review must not exceed the total due.
            $pendingTotal = (float) ServicePayment::query()
                ->where('service_booking_id', $locked->id)
                ->where('status', 'PENDING')
                ->where('kind', '!=', 'REFUND')
                ->sum('amount');

            $available = round(max(0, $locked->balanceDue() - $pendingTotal), 2);

            if ($amount > $available + 0.009) {
                throw new \RuntimeException(
                    'Amount exceeds the outstanding balance of PKR '.number_format($available, 2).'.'
                );
            }

            try {
                $payment = ServicePayment::create([
                    'tenant_id' => $locked->tenant_id,
                    'service_booking_id' => $locked->id,
                    'customer_id' => $locked->customer_id,
                    'amount' => $amount,
                    'method' => CashOnlyPayments::CASH,
                    'reference' => $reference,
                    'status' => 'PENDING',
                    'kind' => $kind,
                    'notes' => $data['notes'] ?? 'Submitted by customer; awaiting physical cash verification.',
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                // Another request won the race for this reference. Return the
                // canonical row rather than double-recording the submission.
                $canonical = ServicePayment::query()
                    ->where('service_booking_id', $locked->id)
                    ->where('reference', $reference)
                    ->first();

                if (! $canonical) {
                    throw $exception;
                }

                $this->assertSubmissionReplayMatches($canonical, $amount, $kind);

                return ['success' => true, 'payment' => $canonical, 'replayed' => true];
            }

            AuditLog::log(
                'SERVICE_PAYMENT_SUBMITTED',
                'Customer submitted a cash service payment of PKR '.number_format($amount, 2).' for booking '.$locked->booking_reference.'; awaiting staff verification.',
                (string) $locked->tenant_id,
                ServicePayment::class,
                (string) $payment->id
            );

            return ['success' => true, 'payment' => $payment];
        });
    }

    private function normalizeSubmissionReference(mixed $reference): ?string
    {
        if ($reference === null) {
            return null;
        }

        $reference = trim((string) $reference);

        return $reference === '' ? null : substr($reference, 0, 60);
    }

    /**
     * A customer-supplied reference is an idempotency key. Replaying it with a
     * different amount or kind is a conflict, never a silent success.
     */
    private function assertSubmissionReplayMatches(ServicePayment $existing, float $amount, string $kind): void
    {
        $sameKind = strtoupper((string) $existing->kind) === $kind;
        $sameAmount = abs((float) $existing->amount - $amount) < 0.005;

        if (! $sameKind || ! $sameAmount) {
            throw new \RuntimeException(
                'This payment reference was already used for a different amount or payment kind. Please use a new reference.'
            );
        }
    }

    /**
     * Verify a manually submitted service payment after the staff member has
     * physically confirmed the cash/offline transfer, and apply it. Nothing is
     * credited while the record is PENDING. Full settlements trigger vendor
     * wallet credit + commission revenue recognition + payout request; refunds
     * reverse the paid balance.
     *
     * @return array{success: bool, message: string, booking?: ServiceBooking}
     */
    public function verifyServicePayment(ServicePayment $payment, ?int $verifiedByUserId = null): array
    {
        // Payment verification, booking balance, wallet credit/debit and payout
        // creation must commit as one unit. A failed commission debit must not
        // leave a credited wallet or a PAID booking behind.
        return DB::transaction(function () use ($payment, $verifiedByUserId): array {
            // Re-read under lock: two staff members must never settle the same
            // pending payment at the same time.
            $payment = ServicePayment::query()->whereKey($payment->id)->lockForUpdate()->first();

            if (! $payment) {
                return ['success' => false, 'message' => 'The payment record could not be found.'];
            }

            // Lock the booking too. Two different PENDING payments on one
            // booking must not both read the same paid_amount and both trigger
            // wallet settlement.
            $booking = ServiceBooking::query()
                ->whereKey($payment->service_booking_id)
                ->lockForUpdate()
                ->first();

            if (! $booking) {
                return ['success' => false, 'message' => 'The service booking for this payment no longer exists.'];
            }

            // Verification is idempotent for already-final records, but a
            // rejected/legacy record must never be silently turned into money.
            if ($payment->status === 'VERIFIED' || $payment->status === 'REFUNDED') {
                return ['success' => true, 'already_verified' => true, 'booking' => $booking];
            }

            if ($payment->status !== 'PENDING') {
                throw ServiceBookingException::invalidState('Only pending payments can be verified.');
            }

            // Historical non-cash labels are preserved for reporting, but they
            // can never be settled through the staff verification path.
            try {
                CashOnlyPayments::assert($payment->method, 'service payments');
            } catch (\InvalidArgumentException $exception) {
                throw ServiceBookingException::invalidState($exception->getMessage());
            }

            if ((string) $payment->tenant_id !== (string) $booking->tenant_id) {
                throw ServiceBookingException::invalidState('The payment and booking belong to different tenants.');
            }

            $amount = round((float) $payment->amount, 2);

            if ($amount <= 0) {
                throw ServiceBookingException::invalidState('A payment must have a positive amount.');
            }

            if (in_array($booking->status, ['CANCELLED', 'DECLINED'], true)) {
                throw ServiceBookingException::invalidState('A cancelled or declined booking cannot receive payments.');
            }

            // Verification is the last line of defence. Pending submissions may
            // have been created by an older caller or two staff members may be
            // settling competing rows; the locked booking balance decides.
            if ($payment->kind === 'REFUND') {
                if ($amount > (float) $booking->paid_amount + 0.009) {
                    throw ServiceBookingException::invalidState(
                        'Refund exceeds the amount already paid on this booking.'
                    );
                }
            } else {
                $balance = round($booking->balanceDue(), 2);

                if ($amount > $balance + 0.009) {
                    throw ServiceBookingException::invalidState(
                        'Payment exceeds the current outstanding balance of PKR '.number_format($balance, 2).'.'
                    );
                }
            }

            $payment->update([
                'status' => $payment->kind === 'REFUND' ? 'REFUNDED' : 'VERIFIED',
                'verified_by' => $verifiedByUserId,
                'verified_at' => now(),
            ]);

            AuditLog::log(
                'SERVICE_PAYMENT_VERIFIED',
                'Service payment '.$payment->id.' verified; booking balance and wallet settlement evaluated.',
                (string) $payment->tenant_id,
                ServicePayment::class,
                (string) $payment->id
            );

            if ($payment->kind === 'REFUND') {
                $this->reverseBookingPayment($booking, (float) $payment->amount);

                $remainingPaid = (float) $booking->fresh()->paid_amount;
                $totalDue = max(0.0, (float) $booking->quoted_amount - (float) $booking->discount_amount);

                $booking->update([
                    'payment_status' => $remainingPaid <= 0
                        ? 'REFUNDED'
                        : ($remainingPaid < $totalDue ? 'PARTIAL' : 'PAID'),
                ]);

                return ['success' => true, 'refund' => true, 'booking' => $booking->fresh()];
            }

            $booking->update(['paid_amount' => (float) $booking->paid_amount + (float) $payment->amount]);

            // A caller-supplied "FULL" label must never settle a booking: only
            // the actual summed amounts can drive the paid state.
            if ($booking->balanceDue() <= 0) {
                $booking->update(['payment_status' => 'PAID']);
                $this->settleServiceBookingPayment($booking);
            } elseif ($payment->kind === 'ADVANCE') {
                $booking->update(['payment_status' => 'ADVANCE_PAID']);
            } else {
                $booking->update(['payment_status' => $booking->balanceDue() > 0 ? 'PARTIAL' : 'PAID']);
            }

            return ['success' => true, 'booking' => $booking->fresh()];
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Finance helpers
    // ─────────────────────────────────────────────────────────────

    public function commissionRateFor(Tenant $tenant): float
    {
        $rate = (float) ($tenant->commission_rate ?? 0);

        if ($rate > 1) {
            // Stored as whole percent (20 => 20%).
            $rate = $rate / 100;
        }

        if ($rate <= 0) {
            $rate = (float) PlatformSetting::get('marketplace.commission.default_rate', 0.05);
        }

        return min(0.95, max(0.0, $rate));
    }

    public function resolveAdvancePercent(): float
    {
        $percent = (float) PlatformSetting::get('marketplace.advance.default_percent', 30);

        return max(0.0, $percent);
    }

    public function calculateFinancials(float $quotedAmount, ?float $discountAmount = 0.0, ?Tenant $tenant = null): array
    {
        $subtotal = max(0.0, $quotedAmount - (float) ($discountAmount ?? 0.0));
        $rate = $tenant ? $this->commissionRateFor($tenant) : (float) PlatformSetting::get('marketplace.commission.default_rate', 0.05);
        $commission = round($subtotal * $rate, 2);
        $net = round($subtotal - $commission, 2);
        $advancePercent = $this->resolveAdvancePercent();
        $advance = round($subtotal * $advancePercent / 100, 2);

        return [
            'quoted_amount' => round($quotedAmount, 2),
            'discount_amount' => round((float) ($discountAmount ?? 0.0), 2),
            'commission_amount' => $commission,
            'net_vendor_amount' => $net,
            'advance_amount' => $advance,
            'balance_due' => $subtotal,
        ];
    }

    public function getVendorBookingsStats(string $tenantId): array
    {
        $bookings = ServiceBooking::query()
            ->where('tenant_id', $tenantId)
            ->get(['status', 'quoted_amount', 'paid_amount', 'event_date']);

        $activeStatuses = ['ACCEPTED', 'CONFIRMED', 'IN_PROGRESS'];

        $activeBookings = $bookings->whereIn('status', $activeStatuses);
        $upcomingBookings = $activeBookings->filter(
            fn ($booking) => $booking->event_date?->gte(now()->toDateString())
        );

        return [
            'total_bookings' => $bookings->count(),
            'active' => $activeBookings->count(),
            'upcoming' => $upcomingBookings->count(),
            'pending' => $bookings->whereIn('status', ['PENDING_VENDOR', 'QUOTE_SENT'])->count(),
            'confirmed' => $bookings->whereIn('status', ['ACCEPTED', 'CONFIRMED', 'IN_PROGRESS'])->count(),
            'revenue' => (float) $bookings->whereNotIn('status', ['CANCELLED', 'DECLINED'])->sum('quoted_amount'),
            'total_paid' => (float) $bookings->whereNotIn('status', ['CANCELLED', 'DECLINED'])->sum('paid_amount'),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Payouts
    // ─────────────────────────────────────────────────────────────

    public function requestVendorPayout(string $tenantId, int $vendorUserId, float $amount, string $method = CashOnlyPayments::CASH, ?array $accountDetails = null): VendorPayout
    {
        CashOnlyPayments::assert($method, 'payouts');

        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ServiceBookingException::invalidState('A payout amount must be greater than zero.');
        }

        return DB::transaction(function () use ($tenantId, $amount, $method, $accountDetails): VendorPayout {
            // Lock the wallet while checking available funds: two simultaneous
            // requests must never both pass the balance guard. Outstanding
            // REQUESTED payouts are treated as reserved funds even though the
            // actual debit happens only when a platform finance user processes
            // the payout.
            $wallet = VendorWallet::query()
                ->where('tenant_id', $tenantId)
                ->where('is_primary', true)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                throw ServiceBookingException::invalidState('No primary vendor wallet is available for this payout.');
            }

            $reserved = (float) VendorPayout::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'REQUESTED')
                ->sum('amount');

            if ((float) $wallet->balance - $reserved + 0.009 < $amount) {
                throw ServiceBookingException::invalidState(
                    'The vendor wallet has insufficient available balance for this payout request.'
                );
            }

            return VendorPayout::create([
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'method' => $method,
                'account_details' => $accountDetails ?? ['account_name' => 'Vendor Wallet Settlement'],
                'status' => 'REQUESTED',
                'reference' => 'PO-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
                'note' => 'Auto-requested after full service payment settlement.',
                'requested_at' => now(),
            ]);
        });
    }

    public function processVendorPayout(VendorPayout $payout, int $processorUserId): VendorPayout
    {
        return DB::transaction(function () use ($payout, $processorUserId) {
            $locked = VendorPayout::query()
                ->whereKey($payout->id)
                ->where('status', 'REQUESTED')
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw ServiceBookingException::invalidState('Only requested payouts can be processed.');
            }

            $amount = round((float) $locked->amount, 2);

            if ($amount <= 0) {
                throw ServiceBookingException::invalidState('A payout amount must be greater than zero.');
            }

            $wallet = VendorWallet::query()
                ->where('tenant_id', $locked->tenant_id)
                ->where('is_primary', true)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                throw ServiceBookingException::invalidState('No primary vendor wallet is available for this payout.');
            }

            if ((float) $wallet->balance + 0.009 < $amount) {
                throw ServiceBookingException::invalidState(
                    'The vendor wallet has insufficient balance for this payout.'
                );
            }

            // Debit first: a failed debit must never leave a payout marked PAID.
            $this->debitWallet(
                $locked->tenant_id,
                (int) $wallet->id,
                $amount,
                'PAYOUT',
                $locked->id,
                'Vendor payout processed via wallet settlement.'
            );

            $locked->update([
                'status' => 'PAID',
                'processed_by' => $processorUserId,
                'processed_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────

    protected function findTenantBooking(string $serviceBookingId, string $tenantId): ServiceBooking
    {
        $booking = ServiceBooking::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $serviceBookingId)
            ->first();

        if (! $booking) {
            throw ServiceBookingException::notFound('The service booking could not be found for this vendor.');
        }

        return $booking;
    }

    protected function resolveEndTime(array $preferences): ?string
    {
        $startTime = $preferences['start_time'] ?? null;
        $hours = (int) ($preferences['hours'] ?? 0);

        if (! $startTime || $hours <= 0) {
            return null;
        }

        $start = Carbon::parse($startTime);
        $end = $start->copy()->addHours($hours);

        return $end->format('H:i');
    }

    protected function reverseBookingPayment(ServiceBooking $booking, float $amount): void
    {
        $booking->update(['paid_amount' => max(0.0, (float) $booking->paid_amount - $amount)]);

        $wallet = VendorWallet::query()
            ->where('tenant_id', $booking->tenant_id)
            ->where('is_primary', true)
            ->first();

        if ($wallet) {
            $this->debitWallet($booking->tenant_id, (int) $wallet->id, $amount, 'REFUND', null, 'Service booking refund processed via wallet ledger.');
        }
    }

    protected function settleServiceBookingPayment(ServiceBooking $booking): void
    {
        // The booking ledger is the idempotency authority. Re-running a
        // settlement after a retry or a second fully-paid cash payment must
        // never credit the vendor wallet or create another payout.
        $alreadySettled = WalletTransaction::query()
            ->where('service_booking_id', $booking->id)
            ->where('source', 'BOOKING_PAYMENT')
            ->exists();

        if ($alreadySettled) {
            return;
        }

        // Use the financial snapshot stored when the booking was created. A
        // later commission-rate change must never alter a settled payout.
        $gross = round((float) $booking->quoted_amount - (float) $booking->discount_amount, 2);
        $commission = round((float) $booking->commission_amount, 2);
        $net = round((float) $booking->net_vendor_amount, 2);

        // Credit the full amount, then post a real commission debit so the
        // wallet balance always equals the latest ledger balance_after.
        $wallet = $this->creditWallet($booking, $gross, 'BOOKING_PAYMENT', $booking->booking_reference);

        if (! $wallet) {
            return;
        }

        if ($commission > 0) {
            $this->debitWallet(
                $booking->tenant_id,
                (int) $wallet->id,
                $commission,
                'COMMISSION',
                null,
                'Platform commission withheld from service booking settlement.',
                (string) $booking->id
            );
        }

        $this->requestVendorPayout($booking->tenant_id, (int) $wallet->user_id, $net);
    }

    protected function applyVerifiedPayment(ServiceBooking $booking, ServicePayment $payment): void
    {
        if ($payment->kind === 'REFUND') {
            $this->reverseBookingPayment($booking, (float) $payment->amount);
            $booking->update(['payment_status' => 'REFUNDED']);

            return;
        }

        $booking->update(['paid_amount' => (float) $booking->paid_amount + (float) $payment->amount]);

        if ($booking->balanceDue() <= 0) {
            $booking->update(['payment_status' => 'PAID']);
            $this->settleServiceBookingPayment($booking);
        } elseif ($payment->kind === 'ADVANCE') {
            $booking->update(['payment_status' => 'ADVANCE_PAID']);
        } else {
            $booking->update(['payment_status' => $booking->balanceDue() > 0 ? 'PARTIAL' : 'PAID']);
        }
    }

    protected function creditWallet(ServiceBooking $booking, float $amount, string $source, ?string $reference = null): ?VendorWallet
    {
        $wallet = VendorWallet::query()->where('tenant_id', $booking->tenant_id)->where('is_primary', true)->first();

        if (! $wallet) {
            return null;
        }

        // Atomic increment: two concurrent settlements must not lose a credit.
        $wallet->increment('balance', $amount);
        $balance = (float) $wallet->balance;

        WalletTransaction::create([
            'tenant_id' => $booking->tenant_id,
            'vendor_wallet_id' => $wallet->id,
            'type' => 'CREDIT',
            'source' => $source,
            'amount' => $amount,
            'balance_after' => $balance,
            'service_booking_id' => $booking->id,
            'vendor_payout_id' => null,
            'reference' => $reference,
            'note' => 'Vendor wallet credited via service booking settlement.',
        ]);

        return $wallet;
    }

    protected function debitWallet(
        string $tenantId,
        int $walletId,
        float $amount,
        string $source,
        ?string $vendorPayoutId,
        string $note,
        ?string $serviceBookingId = null
    ): ?VendorWallet {
        if ($amount <= 0) {
            throw ServiceBookingException::invalidState('A debit amount must be greater than zero.');
        }

        // Tenant scope is mandatory: a wallet ID from another business must
        // never be debited. The balance guard is applied in the same atomic
        // UPDATE so two concurrent debits cannot overdraw the wallet.
        $affected = VendorWallet::query()
            ->whereKey($walletId)
            ->where('tenant_id', $tenantId)
            ->where('balance', '>=', $amount)
            ->decrement('balance', $amount);

        if ($affected !== 1) {
            throw ServiceBookingException::invalidState(
                'The vendor wallet could not be debited (wrong tenant or insufficient balance).'
            );
        }

        $wallet = VendorWallet::query()->findOrFail($walletId);

        WalletTransaction::create([
            'tenant_id' => $tenantId,
            'vendor_wallet_id' => $wallet->id,
            'type' => 'DEBIT',
            'source' => $source,
            'amount' => $amount,
            'balance_after' => $wallet->balance,
            'service_booking_id' => $serviceBookingId,
            'vendor_payout_id' => $vendorPayoutId,
            'reference' => null,
            'note' => $note,
        ]);

        return $wallet;
    }
}
