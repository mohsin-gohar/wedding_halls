<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Hall;
use App\Support\ShiftWindows;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only availability queries shared by public grids, filters and the
 * booking write path. BookingService delegates its conflict query here so the
 * UI can never disagree with the authoritative reservation check.
 */
class HallAvailabilityService
{
    public const BLOCKING_STATUSES = ['HOLD', 'CONFIRMED'];

    /**
     * Same overlap rules as BookingService's original conflict query:
     * FULL_DAY blocks every shift, individual shifts also conflict through
     * the tenant's configured window overlap/turnover buffer.
     */
    public function conflictQuery(string $hallId, string|Carbon $eventDate, string $slotType): Builder
    {
        $date = $this->dateString($eventDate);
        $normalizedSlot = strtoupper($slotType);
        $conflictingSlots = $normalizedSlot === 'FULL_DAY'
            ? ShiftWindows::KEYS
            : [$normalizedSlot, 'FULL_DAY'];

        $named = Booking::where('hall_id', $hallId)
            ->whereDate('event_date', $date)
            ->whereIn('slot_type', $conflictingSlots)
            ->whereIn('status', self::BLOCKING_STATUSES);

        // Fast path: a direct FULL_DAY/same-shift collision needs no window math.
        if ($named->exists()) {
            return $named;
        }

        $hall = Hall::with('tenant')->find($hallId);
        $tenant = $hall?->tenant;
        $buffer = (int) ($tenant?->shift_buffer_minutes ?? 0);
        $storedWindows = $tenant?->shift_windows;

        if ($buffer <= 0 && $storedWindows === null) {
            return $named;
        }

        $windows = ShiftWindows::normalize($storedWindows);
        $blockingIds = Booking::where('hall_id', $hallId)
            ->whereDate('event_date', $date)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->get()
            ->filter(fn (Booking $booking) => ShiftWindows::conflicts(
                $windows,
                $normalizedSlot,
                (string) $booking->slot_type,
                $buffer
            ))
            ->pluck('id');

        return Booking::whereIn('id', $blockingIds->all());
    }

    public function isSlotAvailable(string $hallId, string|Carbon $eventDate, string $slotType): bool
    {
        return ! $this->conflictQuery($hallId, $eventDate, $slotType)->exists();
    }

    /**
     * All bookings in the given collection that conflict with a shift using
     * this hall's tenant policy. Used by the calendar's conflict display.
     *
     * @param  iterable<Booking>  $bookings
     * @return array<int, Booking>
     */
    public function conflictingBookings(Hall $hall, iterable $bookings, string $slotType): array
    {
        $windows = ShiftWindows::normalize($hall->tenant?->shift_windows);
        $buffer = (int) ($hall->tenant?->shift_buffer_minutes ?? 0);

        return array_values(array_filter(
            $bookings,
            fn (Booking $booking) => ShiftWindows::conflicts(
                $windows,
                $slotType,
                (string) $booking->slot_type,
                $buffer
            )
        ));
    }

    /**
     * Authoritative blocked shifts for one hall on one date.
     *
     * @return array<string, bool> keyed by AFTERNOON/EVENING/NIGHT
     */
    public function blockedSlotsForHall(Hall $hall, string|Carbon $eventDate): array
    {
        $date = $this->dateString($eventDate);

        return $this->blockedSlotsForHalls([$hall->id], $date)['halls'][$hall->id] ?? [];
    }

    /**
     * Blocked shifts for many halls on one date in a single booking query.
     * The returned representative booking lets public grids keep their
     * "Booked / Reserved Hold" labels without a second query.
     *
     * @param  array<int, string>  $hallIds
     * @return array<string, array{halls: array<string, array<string, bool>>, bookings: array<string, Booking>}>
     */
    public function blockedSlotsForHalls(array $hallIds, string|Carbon $eventDate): array
    {
        $date = $this->dateString($eventDate);
        $hallIds = array_values(array_unique(array_filter($hallIds)));
        $halls = Hall::with('tenant')->whereIn('id', $hallIds)->get()->keyBy('id');

        $rows = $hallIds === []
            ? collect()
            : Booking::whereIn('hall_id', $hallIds)
                ->whereDate('event_date', $date)
                ->whereIn('status', self::BLOCKING_STATUSES)
                ->get(['id', 'hall_id', 'event_date', 'slot_type', 'status']);

        $blockedByHall = [];
        $representatives = [];

        foreach ($halls as $hall) {
            $built = $this->blockedForHall($hall, $rows->where('hall_id', $hall->id)->values()->all());
            $blockedByHall[$hall->id] = $built['blocked'];

            foreach ($built['bookings'] as $slot => $booking) {
                $representatives[$hall->id.'_'.$slot] = $booking;
            }
        }

        foreach ($hallIds as $hallId) {
            $blockedByHall[$hallId] ??= [];
        }

        return ['halls' => $blockedByHall, 'bookings' => $representatives];
    }

    /**
     * One booking query for a whole month instead of one query per calendar day.
     *
     * @return array<string, array<string, bool>> keyed by Y-m-d date
     */
    public function monthBlockedSlots(Hall $hall, string|Carbon $start, string|Carbon $end): array
    {
        [$from, $to] = [$this->dateString($start), $this->dateString($end)];

        if ($from > $to) {
            return [];
        }

        $rows = Booking::where('hall_id', $hall->id)
            ->whereBetween('event_date', [$from, $to])
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->get(['id', 'hall_id', 'event_date', 'slot_type', 'status']);

        $grouped = $rows->groupBy(fn (Booking $booking) => $booking->event_date->toDateString());
        $result = [];

        foreach ($this->dateRange($from, $to) as $date) {
            $result[$date] = $this->blockedForHall(
                $hall,
                $grouped->get($date, collect())->values()->all()
            )['blocked'];
        }

        return $result;
    }

    /**
     * Multi-hall equivalent of monthBlockedSlots(), also folded in memory so a
     * recurring Livewire calendar refresh does not issue 60-90 booking queries.
     *
     * @param  array<int, string>  $hallIds
     * @return array<string, array{halls: array<string, array<string, bool>>, bookings: array<string, Booking>}>
     */
    public function blockedSlotsForHallsInRange(array $hallIds, string|Carbon $start, string|Carbon $end): array
    {
        [$from, $to] = [$this->dateString($start), $this->dateString($end)];
        $hallIds = array_values(array_unique(array_filter($hallIds)));

        if ($from > $to || $hallIds === []) {
            return [];
        }

        $halls = Hall::with('tenant')->whereIn('id', $hallIds)->get()->keyBy('id');
        $rows = Booking::whereIn('hall_id', $hallIds)
            ->whereBetween('event_date', [$from, $to])
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->get(['id', 'hall_id', 'event_date', 'slot_type', 'status']);

        $grouped = $rows->groupBy(fn (Booking $booking) => $booking->event_date->toDateString());
        $result = [];

        foreach ($this->dateRange($from, $to) as $date) {
            $dayRows = $grouped->get($date, collect());
            $blockedByHall = [];
            $representatives = [];

            foreach ($halls as $hall) {
                $built = $this->blockedForHall($hall, $dayRows->where('hall_id', $hall->id)->values()->all());
                $blockedByHall[$hall->id] = $built['blocked'];

                foreach ($built['bookings'] as $slot => $booking) {
                    $representatives[$date.'_'.$hall->id.'_'.$slot] = $booking;
                }
            }

            foreach ($hallIds as $hallId) {
                $blockedByHall[$hallId] ??= [];
            }

            $result[$date] = ['halls' => $blockedByHall, 'bookings' => $representatives];
        }

        return $result;
    }

    /**
     * Compact availability flag set for one hall/date.
     *
     * @return array<string, bool>
     */
    public function dayAvailability(Hall $hall, string|Carbon $eventDate, ?array $blocked = null): array
    {
        $blocked ??= $this->blockedSlotsForHall($hall, $eventDate);
        $availability = [];

        foreach (ShiftWindows::BOOKABLE_KEYS as $slot) {
            $availability[strtolower($slot).'_available'] = ! isset($blocked[$slot]);
        }

        $availability['all_booked'] = ! in_array(true, $availability, true);
        $availability['total_booked'] = count($blocked);

        return $availability;
    }

    /**
     * @param  iterable<Booking>  $dayBookings
     * @return array{blocked: array<string, bool>, bookings: array<string, Booking>}
     */
    private function blockedForHall(Hall $hall, iterable $dayBookings): array
    {
        $blocked = [];
        $representatives = [];
        $bookings = is_array($dayBookings) ? $dayBookings : iterator_to_array($dayBookings);

        foreach (ShiftWindows::BOOKABLE_KEYS as $slot) {
            $conflicts = $this->conflictingBookings($hall, $bookings, $slot);
            if ($conflicts !== []) {
                $blocked[$slot] = true;
                $representatives[$slot] = $conflicts[0];
            }
        }

        return ['blocked' => $blocked, 'bookings' => $representatives];
    }

    /**
     * @return array<int, string>
     */
    private function dateRange(string $from, string $to): array
    {
        $dates = [];
        $cursor = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();

        while ($cursor->lte($end)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $dates;
    }

    private function dateString(string|Carbon $date): string
    {
        return $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
    }
}
