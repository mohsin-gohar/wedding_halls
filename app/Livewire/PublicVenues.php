<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\PageVisit;
use App\Models\Venue;
use App\Support\CalendarWindow;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public', ['title' => 'Wedding Venues Marketplace — EventHub Pakistan'])]
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
        // 1. Strict SaaS Visibility: Only show active halls belonging to active, verified/approved tenants
        $publicHalls = Hall::with(['venue', 'tenant'])
            ->where('is_active', true)
            ->whereHas('venue', fn ($venueQuery) => $venueQuery->where('is_active', true))
            ->whereHas('tenant', function ($t) {
                $t->where('is_active', true)
                    ->where('verification_status', 'APPROVED');
            });

        $hasPublicHalls = (clone $publicHalls)->exists();
        $cities = Venue::query()
            ->where('is_active', true)
            ->whereHas('halls', function ($hallQuery) {
                $hallQuery->where('is_active', true)
                    ->whereHas('tenant', function ($tenantQuery) {
                        $tenantQuery->where('is_active', true)
                            ->where('verification_status', 'APPROVED');
                    });
            })
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

        $calendarHallsQuery = Hall::with(['venue', 'tenant'])
            ->where('is_active', true)
            ->whereHas('venue', fn ($venueQuery) => $venueQuery->where('is_active', true))
            ->whereHas('tenant', fn ($t) => $t->where('is_active', true)->where('verification_status', 'APPROVED'));

        $this->applyDiscoveryFilters($calendarHallsQuery);

        $calendarHallsList = $calendarHallsQuery->orderBy('name')->get();
        $totalScopeHalls = max(1, $calendarHallsList->count());

        // When exactly one hall is in scope, preselect it so the "Book Now"
        // action always targets a real venue instead of passing a null hall.
        if ($calendarHallsList->count() === 1 && empty($this->calendarHallId)) {
            $this->calendarHallId = $calendarHallsList->first()->id;
        }

        $monthBookingsQuery = Booking::whereIn('status', ['HOLD', 'CONFIRMED'])
            ->whereBetween('event_date', [$startOfMonth, $endOfMonth]);

        if (! empty($this->calendarHallId)) {
            $monthBookingsQuery->where('hall_id', $this->calendarHallId);
            $effectiveCapacity = 1;
        } else {
            $monthBookingsQuery->whereIn('hall_id', $calendarHallsList->pluck('id'));
            $effectiveCapacity = $totalScopeHalls;
        }

        $monthBookings = $monthBookingsQuery
            ->groupBy('event_date', 'slot_type')
            ->selectRaw('event_date, slot_type, COUNT(DISTINCT hall_id) as booked_count')
            ->get();

        $monthGrid = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dayCarbon = $monthCarbon->copy()->day($d);
            $dateStr = $dayCarbon->format('Y-m-d');

            $dayBookings = $monthBookings->filter(fn ($b) => $b->event_date->format('Y-m-d') === $dateStr);
            $fullDayCount = (int) ($dayBookings->firstWhere('slot_type', 'FULL_DAY')?->booked_count ?? 0);

            $aBooked = ((int) ($dayBookings->firstWhere('slot_type', 'AFTERNOON')?->booked_count ?? 0)) + $fullDayCount;
            $eBooked = ((int) ($dayBookings->firstWhere('slot_type', 'EVENING')?->booked_count ?? 0)) + $fullDayCount;
            $nBooked = ((int) ($dayBookings->firstWhere('slot_type', 'NIGHT')?->booked_count ?? 0)) + $fullDayCount;

            $aAvailable = $aBooked < $effectiveCapacity;
            $eAvailable = $eBooked < $effectiveCapacity;
            $nAvailable = $nBooked < $effectiveCapacity;

            $monthGrid[$d] = [
                'day' => $d,
                'date' => $dateStr,
                'dayOfWeek' => $dayCarbon->dayOfWeek,
                'dayName' => $dayCarbon->format('D'),
                'isPast' => $dayCarbon->isPast() && ! $dayCarbon->isToday(),
                'isToday' => $dayCarbon->isToday(),
                'afternoon_available' => $aAvailable,
                'evening_available' => $eAvailable,
                'night_available' => $nAvailable,
                'all_booked' => (! $aAvailable && ! $eAvailable && ! $nAvailable),
                'total_booked' => $aBooked + $eBooked + $nBooked,
            ];
        }

        // Details for currently selected date
        $selCarbon = Carbon::parse($this->selectedCalendarDate);
        $selDateStr = $selCarbon->format('Y-m-d');
        $selDayBookings = $monthBookings->filter(fn ($b) => $b->event_date->format('Y-m-d') === $selDateStr);
        $selFullDay = (int) ($selDayBookings->firstWhere('slot_type', 'FULL_DAY')?->booked_count ?? 0);

        $selAfternoonBooked = (((int) ($selDayBookings->firstWhere('slot_type', 'AFTERNOON')?->booked_count ?? 0)) + $selFullDay) >= $effectiveCapacity;
        $selEveningBooked = (((int) ($selDayBookings->firstWhere('slot_type', 'EVENING')?->booked_count ?? 0)) + $selFullDay) >= $effectiveCapacity;
        $selNightBooked = (((int) ($selDayBookings->firstWhere('slot_type', 'NIGHT')?->booked_count ?? 0)) + $selFullDay) >= $effectiveCapacity;

        $selectedDayDetails = [
            'date' => $selDateStr,
            'formattedDate' => $selCarbon->format('l, M jS'),
            'afternoon' => [
                'name' => 'AFTERNOON',
                'time' => '11 AM - 3 PM',
                'icon' => '☀️',
                'isBooked' => $selAfternoonBooked,
            ],
            'evening' => [
                'name' => 'EVENING',
                'time' => '4 PM - 8 PM',
                'icon' => '🌄',
                'isBooked' => $selEveningBooked,
            ],
            'night' => [
                'name' => 'NIGHT',
                'time' => '9 PM - 1 AM',
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
        if ($this->city !== 'All' && $this->city !== '') {
            $query->whereHas('venue', function ($q) {
                $q->where('city', $this->city);
            });
        }

        if (trim($this->search) !== '') {
            $query->where('name', 'like', '%'.trim($this->search).'%');
        }

        $guests = $this->normalizedGuestCount();
        if ($guests > 0) {
            $query->where('max_capacity', '>=', $guests);
        }

        $eventDate = $this->normalizedEventDate();
        $shift = $this->normalizedShift();
        if ($eventDate && $shift) {
            $query->whereDoesntHave('bookings', function ($booking) use ($eventDate, $shift) {
                $booking->whereIn('status', ['HOLD', 'CONFIRMED'])
                    ->whereDate('event_date', $eventDate->toDateString())
                    ->where(function ($slot) use ($shift) {
                        $slot->where('slot_type', $shift)
                            ->orWhere('slot_type', 'FULL_DAY');
                    });
            });
        }
    }
}
