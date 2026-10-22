<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\PageVisit;
use App\Models\Venue;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public')]
class PublicVenues extends Component
{
    use WithPagination;

    #[Url]
    public string $city = 'All';

    #[Url]
    public string $search = '';

    public string $calendarMonth = '';

    public string $selectedCalendarDate = '';

    public ?string $calendarHallId = null;

    public int $selectedYear = 2026;

    public int $selectedMonthNumber = 10;

    public array $allowedYears = [2026, 2027, 2028, 2029, 2030];

    public function mount(): void
    {
        $now = now();
        $this->calendarMonth = $now->format('Y-m');
        $this->selectedCalendarDate = $now->format('Y-m-d');
        $this->selectedYear = max(2026, min(2030, (int) $now->format('Y')));
        $this->selectedMonthNumber = (int) $now->format('n');
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
        $this->calendarHallId = null;
        $this->resetPage();
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
        $this->selectedYear = max(2026, min(2030, (int) $this->selectedYear));
        $this->syncCalendarMonth();
    }

    public function updatedSelectedMonthNumber(): void
    {
        $this->selectedMonthNumber = max(1, min(12, (int) $this->selectedMonthNumber));
        $this->syncCalendarMonth();
    }

    private function syncCalendarMonth(): void
    {
        $this->calendarMonth = sprintf('%04d-%02d', $this->selectedYear, $this->selectedMonthNumber);
        $this->selectedCalendarDate = sprintf('%04d-%02d-01', $this->selectedYear, $this->selectedMonthNumber);
    }

    public function previousMonth(): void
    {
        $current = Carbon::createFromFormat('Y-m', $this->calendarMonth)->startOfMonth();
        $prev = $current->subMonth();

        // Support calendar navigation between 2026 and 2030
        if ($prev->year < 2026) {
            return;
        }

        $this->calendarMonth = $prev->format('Y-m');
        $this->selectedYear = (int) $prev->format('Y');
        $this->selectedMonthNumber = (int) $prev->format('n');
        $this->selectedCalendarDate = $prev->format('Y-m-d');
    }

    public function nextMonth(): void
    {
        $current = Carbon::createFromFormat('Y-m', $this->calendarMonth)->startOfMonth();
        $next = $current->addMonth();

        // Support calendar navigation up to 2030-12
        if ($next->year > 2030) {
            return;
        }

        $this->calendarMonth = $next->format('Y-m');
        $this->selectedYear = (int) $next->format('Y');
        $this->selectedMonthNumber = (int) $next->format('n');
        $this->selectedCalendarDate = $next->format('Y-m-d');
    }

    public function selectCalendarDate(string $date): void
    {
        $this->selectedCalendarDate = $date;
    }

    public function triggerBooking(string $slotType, bool $isBooked): void
    {
        if ($isBooked) {
            $formattedDate = Carbon::parse($this->selectedCalendarDate)->format('l, M jS, Y');
            $this->dispatch('luxury-alert', [
                'title' => 'Shift Already Booked',
                'message' => "The {$slotType} shift on {$formattedDate} has already been reserved and locked for another royal celebration. In strict compliance with our single-host protocol, double bookings are prohibited. Please choose another shift or date.",
                'type' => 'gold',
                'confirmText' => 'Acknowledge',
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

        // 2. Filter by city
        if ($this->city !== 'All' && ! empty($this->city)) {
            $query->whereHas('venue', function ($q) {
                $q->where('city', $this->city);
            });
        }

        // Keep the directory search focused on venue names.
        if (! empty($this->search)) {
            $searchTerm = '%'.trim($this->search).'%';
            $query->where('name', 'like', $searchTerm);
        }

        $query->orderBy('name');

        // Count BEFORE pagination (pagination mutates the query)
        $totalHalls = (clone $query)->count();

        $halls = $query->paginate(9);

        // ==========================================
        // CALENDAR SHIFT MATRIX (Month View as in Image 2)
        // ==========================================
        if (empty($this->calendarMonth)) {
            $this->calendarMonth = now()->format('Y-m');
        }
        if (empty($this->selectedCalendarDate)) {
            $this->selectedCalendarDate = now()->format('Y-m-d');
        }

        $monthCarbon = Carbon::createFromFormat('Y-m', $this->calendarMonth)->startOfMonth();
        $monthTitle = $monthCarbon->format('F Y');
        $daysInMonth = $monthCarbon->daysInMonth;
        $startDayOfWeek = $monthCarbon->dayOfWeek; // 0 for Sun, 1 for Mon, ..., 6 for Sat

        $startOfMonth = $monthCarbon->copy()->startOfMonth()->toDateString();
        $endOfMonth = $monthCarbon->copy()->endOfMonth()->toDateString();

        $calendarHallsQuery = Hall::with(['venue', 'tenant'])
            ->where('is_active', true)
            ->whereHas('venue', fn ($venueQuery) => $venueQuery->where('is_active', true))
            ->whereHas('tenant', fn ($t) => $t->where('is_active', true)->where('verification_status', 'APPROVED'));

        if ($this->city !== 'All' && ! empty($this->city)) {
            $calendarHallsQuery->whereHas('venue', fn ($q) => $q->where('city', $this->city));
        }

        $calendarHallsList = $calendarHallsQuery->orderBy('name')->get();
        $totalScopeHalls = max(1, $calendarHallsList->count());

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
            $fullDayCount = (int) ($dayBookings->firstWhere('slot_type', 'FULL_DAY')->booked_count ?? 0);

            $aBooked = ((int) ($dayBookings->firstWhere('slot_type', 'AFTERNOON')->booked_count ?? 0)) + $fullDayCount;
            $eBooked = ((int) ($dayBookings->firstWhere('slot_type', 'EVENING')->booked_count ?? 0)) + $fullDayCount;
            $nBooked = ((int) ($dayBookings->firstWhere('slot_type', 'NIGHT')->booked_count ?? 0)) + $fullDayCount;

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
        $selFullDay = (int) ($selDayBookings->firstWhere('slot_type', 'FULL_DAY')->booked_count ?? 0);

        $selAfternoonBooked = (((int) ($selDayBookings->firstWhere('slot_type', 'AFTERNOON')->booked_count ?? 0)) + $selFullDay) >= $effectiveCapacity;
        $selEveningBooked = (((int) ($selDayBookings->firstWhere('slot_type', 'EVENING')->booked_count ?? 0)) + $selFullDay) >= $effectiveCapacity;
        $selNightBooked = (((int) ($selDayBookings->firstWhere('slot_type', 'NIGHT')->booked_count ?? 0)) + $selFullDay) >= $effectiveCapacity;

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

        // Track page visit for venues listing
        PageVisit::record(
            request()->path(),
            request()->route()->getName(),
            auth()->id() ? (string) auth()->id() : null,
            request()->ip(),
            request()->userAgent() ?? ''
        );

        return view('livewire.public-venues', [
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
}
