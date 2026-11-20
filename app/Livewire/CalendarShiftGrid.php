<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\Venue;
use App\Services\HallAvailabilityService;
use App\Support\ShiftWindows;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CalendarShiftGrid extends Component
{
    public string $currentMonth = '';

    public string $viewMode = 'month';

    public string $anchorDate = '';

    public int $bufferMinutes = 0;

    public string $holdExpiryHours = '';

    /** @var array<string, string> */
    public array $shiftStarts = [];

    /** @var array<string, string> */
    public array $shiftEnds = [];

    public ?string $calendarMessage = null;

    public ?string $selectedVenueId = null;

    public ?string $selectedHallId = null;

    // Quick Details Modal
    public ?Booking $viewingBooking = null;

    // Data-integrity warnings: slot keys with >1 blocking (CONFIRMED/HOLD) booking.
    public array $slotConflicts = [];

    public ?array $conflictDetails = null;

    public bool $showConflictModal = false;

    public bool $showDetailsModal = false;

    protected $listeners = [
        'bookingCreated' => '$refresh',
        'refreshCalendar' => '$refresh',
    ];

    public function mount()
    {
        $user = Auth::user();
        if (! $user || (! $user->isSuperAdmin() && (! $user->tenant_id || $user->isCustomer()))) {
            abort(403, 'Unauthorized. Tenant staff access required.');
        }

        $this->currentMonth = Carbon::now()->format('Y-m');
        $this->anchorDate = Carbon::now()->toDateString();
        $hallsQuery = $this->accessibleHalls();
        $firstHall = $hallsQuery->first();

        if ($firstHall) {
            $this->selectedHallId = $firstHall->id;
            $this->selectedVenueId = $firstHall->venue_id;
            $this->loadShiftPolicy($firstHall->tenant);
        }
    }

    public function updatedSelectedHallId(): void
    {
        $hall = $this->selectedHallId
            ? $this->accessibleHalls()->whereKey($this->selectedHallId)->first()
            : null;
        if ($hall) {
            $this->loadShiftPolicy($hall->tenant);
        }
    }

    public function setView(string $mode): void
    {
        if (! in_array($mode, ['month', 'week', 'day', 'agenda'], true)) {
            return;
        }

        $this->viewMode = $mode;
        if ($this->anchorDate === '') {
            $this->anchorDate = Carbon::now()->toDateString();
        }
        $this->currentMonth = Carbon::parse($this->anchorDate)->format('Y-m');
    }

    public function navigatePrevious(): void
    {
        $this->moveAnchor($this->viewMode === 'day' ? -1 : ($this->viewMode === 'month' ? 0 : -7));
        if ($this->viewMode === 'month') {
            $this->prevMonth();
        }
    }

    public function navigateNext(): void
    {
        $this->moveAnchor($this->viewMode === 'day' ? 1 : ($this->viewMode === 'month' ? 0 : 7));
        if ($this->viewMode === 'month') {
            $this->nextMonth();
        }
    }

    public function saveShiftPolicy(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->isTenantOwner() && ! $user->isManager() && ! $user->isSuperAdmin())) {
            abort(403);
        }

        $hall = $this->selectedHallId
            ? $this->accessibleHalls()->with('tenant')->whereKey($this->selectedHallId)->first()
            : null;
        if (! $hall || ! $hall->tenant) {
            return;
        }

        $this->validate([
            'bufferMinutes' => 'required|integer|min:0|max:720',
            'holdExpiryHours' => 'nullable|integer|min:1|max:720',
            'shiftStarts.*' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/'],
            'shiftEnds.*' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/'],
        ]);

        $windows = [];
        foreach (ShiftWindows::KEYS as $key) {
            $windows[$key] = [
                'start' => substr($this->shiftStarts[$key] ?? ShiftWindows::defaults()[$key]['start'], 0, 5),
                'end' => substr($this->shiftEnds[$key] ?? ShiftWindows::defaults()[$key]['end'], 0, 5),
            ];
        }

        $hall->tenant->update([
            'shift_windows' => $windows,
            'shift_buffer_minutes' => $this->bufferMinutes,
            'hold_expiry_hours' => $this->holdExpiryHours === '' ? null : (int) $this->holdExpiryHours,
        ]);

        AuditLog::log(
            'SHIFT_POLICY_UPDATED',
            'Shift windows, buffer and hold expiry updated.',
            $hall->tenant_id,
            'Tenant',
            $hall->tenant_id
        );
        $this->calendarMessage = 'Shift windows, buffer and hold expiry saved.';
    }

    private function loadShiftPolicy(?Tenant $tenant): void
    {
        $windows = ShiftWindows::normalize($tenant?->shift_windows);
        $this->shiftStarts = [];
        $this->shiftEnds = [];
        foreach ($windows as $key => $window) {
            $this->shiftStarts[$key] = $window['start'];
            $this->shiftEnds[$key] = $window['end'];
        }
        $this->bufferMinutes = (int) ($tenant->shift_buffer_minutes ?? 0);
        $this->holdExpiryHours = $tenant?->hold_expiry_hours ? (string) $tenant->hold_expiry_hours : '';
    }

    private function moveAnchor(int $days): void
    {
        if ($days === 0) {
            return;
        }
        $anchor = $this->anchor()->addDays($days);
        $this->anchorDate = $anchor->toDateString();
        $this->currentMonth = $anchor->format('Y-m');
    }

    private function anchor(): Carbon
    {
        try {
            $anchor = Carbon::createFromFormat('Y-m-d', $this->anchorDate);
            if (! $anchor || $anchor->format('Y-m-d') !== $this->anchorDate) {
                throw new \InvalidArgumentException;
            }
        } catch (\Throwable) {
            $anchor = Carbon::now();
            $this->anchorDate = $anchor->toDateString();
        }

        return $anchor->startOfDay();
    }

    public function updatedSelectedVenueId($venueId)
    {
        $firstHall = $this->accessibleHalls()->where('venue_id', $venueId)->first();
        $this->selectedHallId = $firstHall ? $firstHall->id : null;
    }

    private function accessibleHalls()
    {
        $user = Auth::user();
        $query = Hall::query()->where('is_active', true);

        if (! $user?->isSuperAdmin()) {
            $query->where('tenant_id', $user?->tenant_id ?? '');
        }

        return $query;
    }

    private function normalizedMonth(): Carbon
    {
        try {
            $month = Carbon::createFromFormat('!Y-m', $this->currentMonth);
            if (! $month || $month->format('Y-m') !== $this->currentMonth || $month->year < 2000 || $month->year > 2100) {
                throw new \InvalidArgumentException;
            }
        } catch (\Throwable) {
            $this->currentMonth = Carbon::now()->format('Y-m');
            $month = Carbon::now()->startOfMonth();
        }

        return $month->startOfMonth();
    }

    public function prevMonth()
    {
        $this->currentMonth = $this->normalizedMonth()->subMonth()->format('Y-m');
        $this->anchorDate = $this->currentMonth.'-01';
    }

    public function nextMonth()
    {
        $this->currentMonth = $this->normalizedMonth()->addMonth()->format('Y-m');
        $this->anchorDate = $this->currentMonth.'-01';
    }

    public function goToToday()
    {
        $this->currentMonth = Carbon::now()->format('Y-m');
        $this->anchorDate = Carbon::now()->toDateString();
    }

    public function openWalkInModal(?string $date = null, ?string $slot = null)
    {
        if (! $this->selectedHallId || ! $this->accessibleHalls()->whereKey($this->selectedHallId)->exists()) {
            return;
        }

        $this->dispatch('openWalkInModal', [
            'hall_id' => $this->selectedHallId,
            'event_date' => $date,
            'slot_type' => $slot,
        ]);
    }

    public function viewBookingDetails(string $bookingId)
    {
        $hall = $this->selectedHallId
            ? $this->accessibleHalls()->whereKey($this->selectedHallId)->first()
            : null;
        $this->viewingBooking = $hall
            ? Booking::with(['hall.venue', 'package', 'payments', 'kitchenKot'])
                ->where('hall_id', $hall->id)
                ->find($bookingId)
            : null;
        $this->showDetailsModal = (bool) $this->viewingBooking;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->viewingBooking = null;
    }

    /**
     * Surface two or more blocking bookings on the same hall/date/shift.
     * Read-only reconciliation view: no record is deleted or modified here.
     */
    public function viewSlotConflicts(string $date, string $slot): void
    {
        $hall = $this->selectedHallId
            ? $this->accessibleHalls()->whereKey($this->selectedHallId)->first()
            : null;
        $key = $date.'_'.$slot;

        if (! $hall || ! isset($this->slotConflicts[$key])) {
            return;
        }

        $this->conflictDetails = [
            'date' => $date,
            'slot' => $slot,
            'hall' => $hall->name,
            'bookings' => $this->slotConflicts[$key],
        ];
        $this->showConflictModal = true;
    }

    public function closeConflictModal(): void
    {
        $this->showConflictModal = false;
        $this->conflictDetails = null;
    }

    public function render()
    {
        $user = Auth::user();
        $hallsQuery = $this->accessibleHalls();
        $venuesQuery = Venue::query()->where('is_active', true);
        if (! $user?->isSuperAdmin()) {
            $venuesQuery->where('tenant_id', $user?->tenant_id ?? '');
        }

        $venues = $venuesQuery->get();

        if ($this->selectedVenueId) {
            $hallsQuery->where('venue_id', $this->selectedVenueId);
        }
        $halls = $hallsQuery->get();

        $startOfMonth = $this->normalizedMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $anchor = $this->anchor();
        [$rangeStart, $rangeEnd] = match ($this->viewMode) {
            'week' => [$anchor->copy()->startOfWeek(Carbon::MONDAY), $anchor->copy()->endOfWeek(Carbon::SUNDAY)],
            'day' => [$anchor->copy(), $anchor->copy()],
            'agenda' => [$anchor->copy(), $anchor->copy()->addDays(13)],
            default => [$startOfMonth->copy(), $endOfMonth->copy()],
        };

        $selectedHall = $this->selectedHallId
            ? $this->accessibleHalls()->whereKey($this->selectedHallId)->first()
            : null;

        // Fetch bookings strictly scoped to this tenant and hall.
        // NOTE: event_date is a DATE column; whereBetween() is the only range
        // predicate needed. A redundant whereRaw('date(event_date) ...') was
        // removed: it was driver-coupled (not PostgreSQL-safe) and duplicated
        // the same range check.
        $bookings = [];
        $this->slotConflicts = [];
        if ($selectedHall) {
            $priority = ['CONFIRMED' => 0, 'HOLD' => 1, 'INQUIRY' => 2];
            $bookingsList = Booking::where('tenant_id', $selectedHall->tenant_id)
                ->where('hall_id', $selectedHall->id)
                ->whereDate('event_date', '>=', $rangeStart->toDateString())
                ->whereDate('event_date', '<=', $rangeEnd->toDateString())
                ->whereIn('status', ['CONFIRMED', 'HOLD', 'INQUIRY'])
                ->get()
                ->sortBy(fn ($b) => [$priority[strtoupper($b->status)] ?? 9, (string) $b->created_at])
                ->values();

            // Group ALL bookings per date+shift. The first (by status priority)
            // is shown; every additional *blocking* booking is surfaced as a
            // data-integrity warning instead of being silently hidden with ??=.
            $grouped = [];
            foreach ($bookingsList as $b) {
                $date = $b->event_date->format('Y-m-d');
                $slots = strtoupper($b->slot_type) === 'FULL_DAY'
                    ? ['AFTERNOON', 'EVENING', 'NIGHT']
                    : [strtoupper($b->slot_type)];
                foreach ($slots as $slot) {
                    $grouped[$date.'_'.$slot][] = $b;
                }
            }

            $availability = app(HallAvailabilityService::class);

            foreach ($grouped as $key => $items) {
                $bookings[$key] = $items[0];

                // Use the same window/buffer overlap rules as booking writes so
                // an overbooked shift is never hidden by a naive slot match.
                $blocking = array_values(array_filter(
                    $items,
                    fn ($b) => in_array(strtoupper($b->status), HallAvailabilityService::BLOCKING_STATUSES, true)
                ));
                $slot = substr($key, strpos($key, '_') + 1);
                $conflicts = $availability->conflictingBookings($selectedHall, $blocking, $slot);

                if (count($conflicts) > 1) {
                    $this->slotConflicts[$key] = array_map(fn ($b) => [
                        'id' => $b->id,
                        'customer_name' => $b->customer_name,
                        'event_type' => $b->event_type,
                        'status' => strtoupper($b->status),
                    ], $conflicts);
                }
            }
        }

        // Build days of the month
        $days = [];
        $current = $startOfMonth->copy();
        while ($current <= $endOfMonth) {
            $days[] = $current->copy();
            $current = $current->copy()->addDay();
        }

        $visibleDays = [];
        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $visibleDays[] = $cursor->copy();
            $cursor->addDay();
        }

        return view('livewire.calendar-shift-grid', [
            'venues' => $venues,
            'halls' => $halls,
            'selectedHall' => $selectedHall,
            'days' => $days,
            'visibleDays' => $visibleDays,
            'bookings' => $bookings,
            'monthTitle' => $startOfMonth->format('F Y'),
            'rangeTitle' => $rangeStart->format('d M').' – '.$rangeEnd->format('d M Y'),
            'shiftWindows' => ShiftWindows::normalize($selectedHall?->tenant?->shift_windows),
            'shiftBuffer' => (int) ($selectedHall?->tenant?->shift_buffer_minutes ?? 0),
        ]);
    }
}
