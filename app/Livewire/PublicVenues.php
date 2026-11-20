<?php

namespace App\Livewire;

use App\Models\Hall;
use App\Models\PageVisit;
use App\Models\Venue;
use App\Services\HallAvailabilityService;
use App\Support\CalendarWindow;
use App\Support\ShiftWindows;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public', ['title' => 'Wedding Venues Marketplace — Riwaj Grand Pakistan'])]
class PublicVenues extends Component
{
    use WithPagination;

    #[Url]
    public string $city = 'All';

    #[Url]
    public string $search = '';

    #[Url]
    public string $date = '';

    #[Url]
    public string $shift = '';

    #[Url]
    public string $guests = '';

    #[Url]
    public string $priceMin = '';

    #[Url]
    public string $priceMax = '';

    public string $calendarMonth = '';

    public string $selectedCalendarDate = '';

    public ?string $calendarHallId = null;

    public int $selectedYear = 0;

    public int $selectedMonthNumber = 1;

    public array $allowedYears = [];

    public function mount(): void
    {
        $now = now();
        $this->allowedYears = CalendarWindow::years();
        $this->calendarMonth = $now->format('Y-m');
        $this->selectedCalendarDate = $now->format('Y-m-d');
        $this->selectedYear = CalendarWindow::clampYear((int) $now->format('Y'));
        $this->selectedMonthNumber = CalendarWindow::clampMonth((int) $now->format('n'));

        $requestedDate = $this->normalizedEventDate();
        if ($requestedDate) {
            $this->calendarMonth = $requestedDate->format('Y-m');
            $this->selectedCalendarDate = $requestedDate->format('Y-m-d');
            $this->selectedYear = (int) $requestedDate->format('Y');
            $this->selectedMonthNumber = (int) $requestedDate->format('n');
        }

        // Record the visit once per page load rather than on every Livewire
        // interaction (filtering, calendar navigation, pagination).
        PageVisit::record(
            request()->path(),
            request()->route()?->getName(),
            auth()->id() ? (string) auth()->id() : null,
            request()->ip(),
            request()->userAgent() ?? ''
        );
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCity()
    {
        $this->resetPage();
        $this->calendarHallId = null;
    }

    public function resetFilters()
    {
        $this->city = 'All';
        $this->search = '';
        $this->date = '';
        $this->shift = '';
        $this->guests = '';
        $this->priceMin = '';
        $this->priceMax = '';
        $this->calendarHallId = null;
        $this->resetPage();
    }

    public function normalizedGuestCount(): int
    {
        $guests = (int) $this->guests;

        return ($guests >= 1 && $guests <= 20000) ? $guests : 0;
    }

    public function normalizedShift(): ?string
    {
        return in_array($this->shift, ['AFTERNOON', 'EVENING', 'NIGHT'], true) ? $this->shift : null;
    }

    public function normalizedEventDate(): ?Carbon
    {
        $date = CalendarWindow::parseDate($this->date);

        if (! $date || ! CalendarWindow::contains($date)) {
            return null;
        }

        return $date;
    }

    public function jumpToYear(int $year): void
    {
        if (in_array($year, $this->allowedYears, true)) {
            $this->selectedYear = $year;
            $this->syncCalendarMonth();
        }
    }

    public function updatedSelectedYear(): void
    {
        $this->selectedYear = CalendarWindow::clampYear((int) $this->selectedYear);
        $this->syncCalendarMonth();
    }

    public function updatedSelectedMonthNumber(): void
    {
        $this->selectedMonthNumber = CalendarWindow::clampMonth((int) $this->selectedMonthNumber);
        $this->syncCalendarMonth();
    }

    private function syncCalendarMonth(): void
    {
        $this->selectedYear = CalendarWindow::clampYear($this->selectedYear);
        $this->selectedMonthNumber = CalendarWindow::clampMonth($this->selectedMonthNumber);

        $firstNavigable = CalendarWindow::firstNavigableMonth();
        if ($this->selectedYear === $firstNavigable->year) {
            $this->selectedMonthNumber = max($this->selectedMonthNumber, $firstNavigable->month);
        }

        $this->calendarMonth = sprintf('%04d-%02d', $this->selectedYear, $this->selectedMonthNumber);
        $this->selectedCalendarDate = sprintf('%04d-%02d-01', $this->selectedYear, $this->selectedMonthNumber);
    }

    public function previousMonth(): void
    {
        $current = CalendarWindow::normalizeMonth($this->calendarMonth);
        $prev = $current->copy()->subMonth();

        if ($prev->lt(CalendarWindow::firstNavigableMonth())) {
            return;
        }

        $this->calendarMonth = $prev->format('Y-m');
        $this->selectedYear = (int) $prev->format('Y');
        $this->selectedMonthNumber = (int) $prev->format('n');
        $this->selectedCalendarDate = $prev->format('Y-m-d');
    }

    public function nextMonth(): void
    {
        $current = CalendarWindow::normalizeMonth($this->calendarMonth);
        $next = $current->copy()->addMonth();

        if ($next->year > CalendarWindow::endYear()) {
            return;
        }

        $this->calendarMonth = $next->format('Y-m');
        $this->selectedYear = (int) $next->format('Y');
        $this->selectedMonthNumber = (int) $next->format('n');
        $this->selectedCalendarDate = $next->format('Y-m-d');
    }

    public function selectCalendarDate(string $date): void
    {
        $parsed = CalendarWindow::parseDate($date);

        if (! $parsed || ! CalendarWindow::contains($parsed)) {
            return;
        }

        if ($parsed->format('Y-m') !== $this->calendarMonth) {
            $this->calendarMonth = $parsed->format('Y-m');
            $this->selectedYear = (int) $parsed->format('Y');
            $this->selectedMonthNumber = (int) $parsed->format('n');
        }

        $this->selectedCalendarDate = $parsed->format('Y-m-d');
    }

    public function triggerBooking(string $slotType, bool $isBooked): void
    {
        $selectedDate = CalendarWindow::parseDate($this->selectedCalendarDate);

        if (! $selectedDate || ! CalendarWindow::contains($selectedDate)) {
            $this->dispatch('luxury-alert', [
                'title' => 'Select a Valid Date',
                'message' => 'Please choose an upcoming event date from the availability calendar before reserving a shift.',
                'type' => 'gold',
                'confirmText' => 'Choose Date',
            ]);

            return;
        }

        if (! in_array($slotType, ['AFTERNOON', 'EVENING', 'NIGHT'], true)) {
            return;
        }

        if ($isBooked) {
            $formattedDate = $selectedDate->format('l, M jS, Y');
            $this->dispatch('luxury-alert', [
                'title' => 'Shift Already Booked',
                'message' => "The {$slotType} shift on {$formattedDate} has already been reserved and locked for another royal celebration. In strict compliance with our single-host protocol, double bookings are prohibited. Please choose another shift or date.",
                'type' => 'gold',
                'confirmText' => 'Acknowledge',
            ]);

            return;
        }

        if (empty($this->calendarHallId)) {
            $this->dispatch('luxury-alert', [
                'title' => 'Select a Hall First',
                'message' => 'The availability matrix is currently showing all certified halls. Choose a specific hall from the "Hall Filter" selector above so we can reserve the correct venue for your event.',
                'type' => 'gold',
                'confirmText' => 'Choose Hall',
            ]);

            return;
        }

        $this->dispatch('openWalkInModal', [
            'hall_id' => $this->calendarHallId,
            'event_date' => $this->selectedCalendarDate,
            'slot_type' => $slotType,
        ]);
    }

    public function render()
    {
        // 1. Strict SaaS Visibility: centralized Hall::publicVisible() scope,
        // plus the marketplace opt-in for marketplace-wide discovery pages.
        $publicHalls = Hall::publicVisible()
            ->whereHas('tenant', fn ($tenant) => $tenant->marketplaceVisible())
            ->with(['venue', 'tenant']);

        $hasPublicHalls = (clone $publicHalls)->exists();
        $cities = Venue::query()
            ->where('is_active', true)
            ->whereHas('halls', fn ($hallQuery) => $hallQuery->publicVisible())
            ->whereHas('tenant', fn ($tenant) => $tenant->marketplaceVisible())
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->orderBy('city')
            ->distinct()
            ->pluck('city');

        $query = clone $publicHalls;
        $this->applyDiscoveryFilters($query);
        $query->withAvg('approvedReviews', 'rating')
            ->withCount('approvedReviews')
            ->orderBy('name');

        // Count BEFORE pagination (pagination mutates the query)
        $totalHalls = (clone $query)->count();

        $halls = $query->paginate(9);

        // ==========================================
        // CALENDAR SHIFT MATRIX (Month View as in Image 2)
        // ==========================================
        $this->allowedYears = CalendarWindow::years();
        $monthCarbon = CalendarWindow::normalizeMonth($this->calendarMonth);
        $this->calendarMonth = $monthCarbon->format('Y-m');
        $this->selectedYear = (int) $monthCarbon->format('Y');
        $this->selectedMonthNumber = (int) $monthCarbon->format('n');

        $selectedDate = CalendarWindow::parseDate($this->selectedCalendarDate);
        if (! $selectedDate
            || ! CalendarWindow::contains($selectedDate)
            || ! $selectedDate->isSameMonth($monthCarbon)) {
            $selectedDate = $monthCarbon->isSameMonth(now()) ? Carbon::today() : $monthCarbon->copy()->startOfMonth();
            $this->selectedCalendarDate = $selectedDate->format('Y-m-d');
        }

        $monthTitle = $monthCarbon->format('F Y');
        $daysInMonth = $monthCarbon->daysInMonth;
        $startDayOfWeek = $monthCarbon->dayOfWeek; // 0 for Sun, 1 for Mon, ..., 6 for Sat

        $startOfMonth = $monthCarbon->copy()->startOfMonth()->toDateString();
        $endOfMonth = $monthCarbon->copy()->endOfMonth()->toDateString();

        $calendarHallsQuery = Hall::publicVisible()
            ->whereHas('tenant', fn ($tenant) => $tenant->marketplaceVisible())
            ->with(['venue', 'tenant']);

        $this->applyDiscoveryFilters($calendarHallsQuery);

        $calendarHallsList = $calendarHallsQuery->orderBy('name')->get();
        $totalScopeHalls = max(1, $calendarHallsList->count());

        // When exactly one hall is in scope, preselect it so the "Book Now"
        // action always targets a real venue instead of passing a null hall.
        if ($calendarHallsList->count() === 1 && empty($this->calendarHallId)) {
            $this->calendarHallId = $calendarHallsList->first()->id;
        }

        $availability = app(HallAvailabilityService::class);
        $gridHallIds = ! empty($this->calendarHallId)
            ? [$this->calendarHallId]
            : $calendarHallsList->pluck('id')->all();
        $effectiveCapacity = ! empty($this->calendarHallId) ? 1 : $totalScopeHalls;

        $blockedByDate = $availability->blockedSlotsForHallsInRange($gridHallIds, $startOfMonth, $endOfMonth);
        $monthGrid = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dayCarbon = $monthCarbon->copy()->day($d);
            $dateStr = $dayCarbon->format('Y-m-d');
            $blockedByHall = $blockedByDate[$dateStr]['halls'] ?? [];

            $slotAvailability = [];
            foreach (ShiftWindows::BOOKABLE_KEYS as $slot) {
                $blockedCount = count(array_filter($blockedByHall, fn (array $blocked) => isset($blocked[$slot])));
                $slotAvailability[strtolower($slot).'_available'] = $blockedCount < $effectiveCapacity;
            }

            $monthGrid[$d] = array_merge([
                'day' => $d,
                'date' => $dateStr,
                'dayOfWeek' => $dayCarbon->dayOfWeek,
                'dayName' => $dayCarbon->format('D'),
                'isPast' => $dayCarbon->isPast() && ! $dayCarbon->isToday(),
                'isToday' => $dayCarbon->isToday(),
                'all_booked' => ! in_array(true, $slotAvailability, true),
                'total_booked' => count(array_filter($slotAvailability, fn (bool $available) => ! $available)),
            ], $slotAvailability);
        }

        // Details for currently selected date use the same authoritative rules.
        $selCarbon = Carbon::parse($this->selectedCalendarDate);
        $selDateStr = $selCarbon->format('Y-m-d');
        $selBlocked = $blockedByDate[$selDateStr]['halls']
            ?? $availability->blockedSlotsForHalls($gridHallIds, $selDateStr)['halls'];
        $selBookedCount = [];
        foreach (ShiftWindows::BOOKABLE_KEYS as $slot) {
            $selBookedCount[$slot] = count(array_filter($selBlocked, fn (array $blocked) => isset($blocked[$slot]))) >= $effectiveCapacity;
        }
        $selAfternoonBooked = $selBookedCount['AFTERNOON'];
        $selEveningBooked = $selBookedCount['EVENING'];
        $selNightBooked = $selBookedCount['NIGHT'];

        $policyHall = $calendarHallsList->firstWhere('id', $this->calendarHallId) ?? $calendarHallsList->first();
        $policyWindows = $policyHall?->tenant?->shift_windows;

        $selectedDayDetails = [
            'date' => $selDateStr,
            'formattedDate' => $selCarbon->format('l, M jS'),
            'afternoon' => [
                'name' => 'AFTERNOON',
                'time' => ShiftWindows::labelForSlot($policyWindows, 'AFTERNOON'),
                'icon' => '☀️',
                'isBooked' => $selAfternoonBooked,
            ],
            'evening' => [
                'name' => 'EVENING',
                'time' => ShiftWindows::labelForSlot($policyWindows, 'EVENING'),
                'icon' => '🌄',
                'isBooked' => $selEveningBooked,
            ],
            'night' => [
                'name' => 'NIGHT',
                'time' => ShiftWindows::labelForSlot($policyWindows, 'NIGHT'),
                'icon' => '🌙',
                'isBooked' => $selNightBooked,
            ],
        ];

        return view('livewire.public-venues', [
            'searchDate' => $this->normalizedEventDate(),
            'searchShift' => $this->normalizedShift(),
            'searchGuests' => $this->normalizedGuestCount(),
            'halls' => $halls,
            'totalHalls' => $totalHalls,
            'cities' => $cities,
            'hasPublicHalls' => $hasPublicHalls,
            'monthTitle' => $monthTitle,
            'startDayOfWeek' => $startDayOfWeek,
            'monthGrid' => $monthGrid,
            'selectedDayDetails' => $selectedDayDetails,
            'calendarHallsList' => $calendarHallsList,
        ]);
    }

    private function applyDiscoveryFilters($query): void
    {
        $city = trim($this->city);
        if ($city !== '' && strcasecmp($city, 'All') !== 0) {
            $query->whereHas('venue', function ($q) use ($city) {
                $q->whereRaw('LOWER(TRIM(city)) = ?', [mb_strtolower($city)]);
            });
        }

        if (trim($this->search) !== '') {
            $query->where('name', 'like', '%'.trim($this->search).'%');
        }

        $guests = $this->normalizedGuestCount();
        if ($guests > 0) {
            $query->where('max_capacity', '>=', $guests);
        }

        $min = is_numeric($this->priceMin) ? (float) $this->priceMin : null;
        $max = is_numeric($this->priceMax) ? (float) $this->priceMax : null;
        if ($min !== null) {
            $query->where('base_rent', '>=', $min);
        }
        if ($max !== null) {
            $query->where('base_rent', '<=', $max);
        }

        $eventDate = $this->normalizedEventDate();
        $shift = $this->normalizedShift();
        if ($eventDate && $shift) {
            // Use the same conflict engine as booking writes (including tenant
            // shift windows and turnover buffer), not a naive slot_type match.
            $visibleIds = Hall::publicVisible()->pluck('halls.id')->all();
            $blocked = app(HallAvailabilityService::class)
                ->blockedSlotsForHalls($visibleIds, $eventDate->toDateString())['halls'];
            $blockedIds = array_keys(array_filter($blocked, fn (array $slots) => isset($slots[$shift])));

            if ($blockedIds !== []) {
                $query->whereNotIn('halls.id', $blockedIds);
            }
        }
    }
}
