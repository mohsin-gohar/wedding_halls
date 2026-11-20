<?php

namespace App\Services;

use App\Events\ServiceBookingCreatedEvent;
use App\Exceptions\ServiceBookingException;
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
use Carbon\Carbon;
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
     * Vendor declines a quotation request — closes the RFQ for that vendor.
     */
    public function declineQuote(int $rfqId, ?int $vendorUserId = null): void
    {
        $rfq = RFQ::query()->find($rfqId);

        if (! $rfq) {
            throw ServiceBookingException::notFound('The quotation request could not be found.');
        }

        if ($rfq->status !== 'OPEN') {
            return;
        }

        $rfq->update(['status' => 'CLOSED']);

        RFQBid::query()
            ->where('rfq_id', $rfq->id)
            ->where('status', 'PENDING')
            ->update(['status' => 'REJECTED']);
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
                    'method' => 'REFUND',
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
     */
    public function recordServicePayment(ServiceBooking $booking, float $amount, string $method, string $kind = 'INSTALLMENT'): ServicePayment
    {
        return DB::transaction(function () use ($booking, $amount, $method, $kind): ServicePayment {
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
            $booking = $payment->serviceBooking()->first();

            if (! $booking) {
                return ['success' => false, 'message' => 'The service booking for this payment no longer exists.'];
            }

            if ($payment->status === 'VERIFIED' || $payment->status === 'REFUNDED') {
                return ['success' => true, 'already_verified' => true, 'booking' => $booking];
            }

            $payment->update([
                'status' => $payment->kind === 'REFUND' ? 'REFUNDED' : 'VERIFIED',
                'verified_by' => $verifiedByUserId,
                'verified_at' => now(),
            ]);

            if ($payment->kind === 'REFUND') {
                $this->reverseBookingPayment($booking, (float) $payment->amount);

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

    public function requestVendorPayout(string $tenantId, int $vendorUserId, float $amount, string $method = 'WALLET', ?array $accountDetails = null): VendorPayout
    {
        $payout = VendorPayout::create([
            'tenant_id' => $tenantId,
            'amount' => $amount,
            'method' => $method,
            'account_details' => $accountDetails ?? ['account_name' => 'Vendor Wallet Settlement'],
            'status' => 'REQUESTED',
            'reference' => 'PO-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
            'note' => 'Auto-requested after full service payment settlement.',
            'requested_at' => now(),
        ]);

        return $payout;
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

            $wallet = VendorWallet::query()
                ->where('tenant_id', $locked->tenant_id)
                ->where('is_primary', true)
                ->first();

            if (! $wallet) {
                throw ServiceBookingException::invalidState('No primary vendor wallet is available for this payout.');
            }

            // Debit first: a failed debit must never leave a payout marked PAID.
            $this->debitWallet(
                $locked->tenant_id,
                (int) $wallet->id,
                (float) $locked->amount,
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
