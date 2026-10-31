<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\HallView;
use App\Models\HallWishlist;
use App\Models\Package;
use App\Models\PageVisit;
use App\Support\CalendarWindow;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class PublicHallDetails extends Component
{
    public Hall $hall;

    public string $activePhoto = '';

    public bool $isWishlisted = false;

    public int $wishlistCount = 0;

    public int $totalViews = 0;

    public function mount(?string $slug = null, ?string $id = null)
    {
        $identifier = $slug ?? $id;

        if (! $identifier) {
            abort(404, 'Venue not found.');
        }

        // 1. Strict Visibility: Support resolving by slug (preferred) or UUID fallback
        $isUuid = Str::isUuid($identifier);
        $this->hall = Hall::with(['venue', 'tenant'])
            ->where(function ($q) use ($identifier, $isUuid) {
                $q->where('slug', $identifier);
                if ($isUuid) {
                    $q->orWhere('id', $identifier);
                }
            })
            ->firstOrFail();

        if (! $this->hall->is_active) {
            abort(404, 'This venue space is currently inactive.');
        }

        if (! $this->hall->tenant || ! $this->hall->tenant->is_active || $this->hall->tenant->verification_status !== 'APPROVED') {
            abort(404, 'Venue is pending verification or currently unavailable.');
        }

        $images = is_array($this->hall->images) ? $this->hall->images : [];
        $this->activePhoto = ! empty($this->hall->cover_image)
            ? $this->hall->cover_image
            : ($images[0] ?? 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop');

        // Track page visit
        PageVisit::record(
            request()->path(),
            request()->route()->getName(),
            auth()->id() ? (string) auth()->id() : null,
            request()->ip(),
            request()->userAgent() ?? ''
        );

        // Track hall view
        $view = HallView::record(
            $this->hall->id,
            $this->hall->tenant_id,
            auth()->id() ? (string) auth()->id() : null,
            request()->ip(),
            request()->userAgent() ?? ''
        );

        // Compute analytics for this hall
        $this->totalViews = HallView::where('hall_id', $this->hall->id)->count();

        // Wishlist status
        $sessionId = session()->getId();
        $userId = auth()->id() ? (string) auth()->id() : null;
        $this->isWishlisted = HallWishlist::isWishlisted($this->hall->id, $userId, $sessionId);
        $this->wishlistCount = HallWishlist::countWishlists($this->hall->id);
        $now = now();
        $this->allowedYears = CalendarWindow::years();
        $this->calendarMonth = $now->format('Y-m');
        $this->selectedYear = CalendarWindow::clampYear((int) $now->format('Y'));
        $this->selectedMonthNumber = CalendarWindow::clampMonth((int) $now->format('n'));

        $requestedDate = CalendarWindow::parseDate((string) request()->query('date', ''));
        if ($requestedDate && CalendarWindow::contains($requestedDate)) {
            $this->calendarMonth = $requestedDate->format('Y-m');
            $this->selectedYear = (int) $requestedDate->format('Y');
            $this->selectedMonthNumber = (int) $requestedDate->format('n');
            $this->selectedDate = $requestedDate->format('Y-m-d');
            $this->monthOffset = (int) Carbon::today()->startOfMonth()->diffInMonths($requestedDate->copy()->startOfMonth(), false);
        } else {
            $this->selectFirstBookableDateForCurrentMonth();
        }
    }

    public string $calendarMonth = '';

    public int $selectedYear = 0;

    public int $selectedMonthNumber = 1;

    public array $allowedYears = [];

    public int $monthOffset = 0; // 0 = current month, 1 = +1 month, 2 = +2 month

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
        $today = Carbon::today()->startOfMonth();
        $target = Carbon::createFromFormat('Y-m', $this->calendarMonth)->startOfMonth();
        $this->monthOffset = (int) $today->diffInMonths($target, false);
        $this->selectFirstBookableDateForCurrentMonth();
    }

    public function prevMonth(): void
    {
        $this->previousMonth();
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
        $today = Carbon::today()->startOfMonth();
        $this->monthOffset = (int) $today->diffInMonths($prev, false);
        $this->selectFirstBookableDateForCurrentMonth();
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
        $today = Carbon::today()->startOfMonth();
        $this->monthOffset = (int) $today->diffInMonths($next, false);
        $this->selectFirstBookableDateForCurrentMonth();
    }

    public function setMonth(int $offset): void
    {
        $today = Carbon::today()->startOfMonth();
        $maxOffset = ((CalendarWindow::endYear() - $today->year) * 12) + (12 - $today->month);
        $this->monthOffset = max(0, min($maxOffset, $offset));
        $target = $today->copy()->addMonths($this->monthOffset);
        $this->calendarMonth = $target->format('Y-m');
        $this->selectedYear = (int) $target->format('Y');
        $this->selectedMonthNumber = (int) $target->format('n');
        $this->selectFirstBookableDateForCurrentMonth();
    }

    public function selectCalendarDate(string $date): void
    {
        $this->setSelectedDate($date);
    }

    public function triggerBooking(string $slotType, bool $isBooked): void
    {
        $selectedDate = CalendarWindow::parseDate($this->selectedDate);

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
                'message' => "The {$slotType} shift on {$formattedDate} for {$this->hall->name} has already been reserved and locked for an event. In strict compliance with our single-host protocol, double bookings are prohibited. Please choose another shift or date.",
                'type' => 'gold',
                'confirmText' => 'Acknowledge',
            ]);

            return;
        }

        $this->dispatch('openWalkInModal', [
            'hall_id' => $this->hall->id,
            'event_date' => $this->selectedDate,
            'slot_type' => $slotType,
        ]);
    }

    public function setPhoto(string $url)
    {
        // Validate that the requested photo is actually part of this hall's gallery or cover image
        $images = is_array($this->hall->images) ? $this->hall->images : [];
        if ($url === $this->hall->cover_image || in_array($url, $images)) {
            $this->activePhoto = $url;
        }
    }

    public string $selectedDate = '';

    public function setSelectedDate(string $date): void
    {
        $selectedDate = CalendarWindow::parseDate($date);

        if (! $selectedDate || ! CalendarWindow::contains($selectedDate)) {
            return;
        }

        $targetMonth = CalendarWindow::normalizeMonth($this->calendarMonth);

        if (! $selectedDate->isSameMonth($targetMonth)
            || ! $this->dateHasAvailableShift($selectedDate)) {
            return;
        }

        $this->selectedDate = $selectedDate->format('Y-m-d');
    }

    private function selectFirstBookableDateForCurrentMonth(): void
    {
        $today = Carbon::today();
        $targetMonth = CalendarWindow::normalizeMonth($this->calendarMonth);
        $startDate = $targetMonth->isSameMonth($today)
            ? $today
            : $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        $bookedSlotsByDate = Booking::where('hall_id', $this->hall->id)
            ->whereBetween('event_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereIn('status', ['CONFIRMED', 'HOLD'])
            ->get(['event_date', 'slot_type'])
            ->groupBy(fn (Booking $booking) => $booking->event_date->format('Y-m-d'));

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $slots = $bookedSlotsByDate->get($date->format('Y-m-d'), collect())->pluck('slot_type');

            if (! $slots->contains('FULL_DAY')
                && $slots->intersect(['AFTERNOON', 'EVENING', 'NIGHT'])->count() < 3) {
                $this->selectedDate = $date->format('Y-m-d');

                return;
            }
        }

        $this->selectedDate = $startDate->format('Y-m-d');
    }

    private function dateHasAvailableShift(Carbon $date): bool
    {
        $bookedSlots = Booking::where('hall_id', $this->hall->id)
            ->whereDate('event_date', $date)
            ->whereIn('status', ['CONFIRMED', 'HOLD'])
            ->pluck('slot_type');

        return ! $bookedSlots->contains('FULL_DAY')
            && $bookedSlots->intersect(['AFTERNOON', 'EVENING', 'NIGHT'])->count() < 3;
    }

    public function toggleWishlist()
    {
        $sessionId = session()->getId();
        $userId = auth()->id() ? (string) auth()->id() : null;

        $isNowWishlisted = HallWishlist::toggle(
            $this->hall->id,
            $this->hall->tenant_id,
            $userId,
            $sessionId
        );

        $this->isWishlisted = $isNowWishlisted;
        $this->wishlistCount = HallWishlist::countWishlists($this->hall->id);

        $this->dispatch('wishlistUpdated', isWishlisted: $isNowWishlisted, count: $this->wishlistCount);
    }

    public function render()
    {
        $today = Carbon::today();

        $this->allowedYears = CalendarWindow::years();
        $targetDate = CalendarWindow::normalizeMonth($this->calendarMonth);
        $this->calendarMonth = $targetDate->format('Y-m');
        $this->selectedYear = (int) $targetDate->format('Y');
        $this->selectedMonthNumber = (int) $targetDate->format('n');

        $selectedDate = CalendarWindow::parseDate($this->selectedDate);
        if (! $selectedDate
            || ! CalendarWindow::contains($selectedDate)
            || ! $selectedDate->isSameMonth($targetDate)) {
            $selectedDate = $targetDate->isSameMonth($today) ? $today : $targetDate->copy()->startOfMonth();
            $this->selectedDate = $selectedDate->format('Y-m-d');
        }

        $monthTitle = $targetDate->format('F Y');
        $monthName = strtoupper($targetDate->format('F'));
        $year = $targetDate->format('Y');

        $startOfMonth = $targetDate->copy()->startOfMonth();
        $endOfMonth = $targetDate->copy()->endOfMonth();
        $daysInMonth = $targetDate->daysInMonth;

        // 3 Upcoming Months List for quick selector tabs
        $upcomingMonths = [
            0 => ['label' => $today->format('M Y'), 'name' => $today->format('F')],
            1 => ['label' => $today->copy()->addMonths(1)->format('M Y'), 'name' => $today->copy()->addMonths(1)->format('F')],
            2 => ['label' => $today->copy()->addMonths(2)->format('M Y'), 'name' => $today->copy()->addMonths(2)->format('F')],
        ];

        // Generate calendar days for the entire target month
        $calendarDays = [];
        $cursor = $startOfMonth->copy();
        while ($cursor->lte($endOfMonth)) {
            $calendarDays[] = $cursor->copy();
            $cursor->addDay();
        }

        // Sunday-first weekday offset (0 = Sunday to 6 = Saturday)
        $startDayOfWeek = $startOfMonth->dayOfWeek;

        // Query bookings for this full month
        $startDate = $startOfMonth->format('Y-m-d');
        $endDate = $endOfMonth->format('Y-m-d');

        $bookingRows = Booking::where('hall_id', $this->hall->id)
            ->whereBetween('event_date', [$startDate, $endDate])
            ->whereIn('status', ['CONFIRMED', 'HOLD'])
            ->select(['id', 'hall_id', 'event_date', 'slot_type', 'status', 'event_type'])
            ->get();

        $bookings = collect();
        foreach ($bookingRows as $booking) {
            $date = $booking->event_date->format('Y-m-d');
            $slots = $booking->slot_type === 'FULL_DAY'
                ? ['AFTERNOON', 'EVENING', 'NIGHT']
                : [$booking->slot_type];

            foreach ($slots as $slot) {
                $bookings->put($date.'_'.$slot, $booking);
            }
        }

        // Build monthGrid for Image 2 design
        $monthGrid = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dayCarbon = $startOfMonth->copy()->day($d);
            $dateStr = $dayCarbon->format('Y-m-d');

            $dayBookings = $bookingRows->filter(fn ($b) => $b->event_date->format('Y-m-d') === $dateStr);
            $hasFullDay = $dayBookings->contains(fn ($b) => $b->slot_type === 'FULL_DAY');

            $aBooked = $hasFullDay || $dayBookings->contains(fn ($b) => $b->slot_type === 'AFTERNOON');
            $eBooked = $hasFullDay || $dayBookings->contains(fn ($b) => $b->slot_type === 'EVENING');
            $nBooked = $hasFullDay || $dayBookings->contains(fn ($b) => $b->slot_type === 'NIGHT');

            $monthGrid[$d] = [
                'day' => $d,
                'date' => $dateStr,
                'dayOfWeek' => $dayCarbon->dayOfWeek,
                'dayName' => $dayCarbon->format('D'),
                'isPast' => $dayCarbon->isPast() && ! $dayCarbon->isToday(),
                'isToday' => $dayCarbon->isToday(),
                'afternoon_available' => ! $aBooked,
                'evening_available' => ! $eBooked,
                'night_available' => ! $nBooked,
                'all_booked' => ($aBooked && $eBooked && $nBooked),
                'total_booked' => ($aBooked ? 1 : 0) + ($eBooked ? 1 : 0) + ($nBooked ? 1 : 0),
            ];
        }

        // Details for currently selected date
        $selDateStr = $this->selectedDate ?: $startOfMonth->format('Y-m-d');
        $selCarbon = Carbon::parse($selDateStr);
        $selDayBookings = $bookingRows->filter(fn ($b) => $b->event_date->format('Y-m-d') === $selDateStr);
        $selHasFullDay = $selDayBookings->contains(fn ($b) => $b->slot_type === 'FULL_DAY');

        $selAfternoonBooked = $selHasFullDay || $selDayBookings->contains(fn ($b) => $b->slot_type === 'AFTERNOON');
        $selEveningBooked = $selHasFullDay || $selDayBookings->contains(fn ($b) => $b->slot_type === 'EVENING');
        $selNightBooked = $selHasFullDay || $selDayBookings->contains(fn ($b) => $b->slot_type === 'NIGHT');

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

        // Ensure packages belong to this specific tenant or are platform standard offerings
        $packages = Package::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('tenant_id')
                    ->orWhere('tenant_id', $this->hall->tenant_id);
            })
            ->get();

        return view('livewire.public-hall-details', [
            'today' => $today,
            'targetDate' => $targetDate,
            'monthTitle' => $monthTitle,
            'monthName' => $monthName,
            'year' => $year,
            'upcomingMonths' => $upcomingMonths,
            'calendarDays' => $calendarDays,
            'startDayOfWeek' => $startDayOfWeek,
            'days' => $calendarDays, // alias for backwards compatibility
            'bookings' => $bookings,
            'packages' => $packages,
            'images' => is_array($this->hall->images) ? $this->hall->images : [],
            'videos' => is_array($this->hall->videos) ? $this->hall->videos : [],
            'isWishlisted' => $this->isWishlisted,
            'wishlistCount' => $this->wishlistCount,
            'totalViews' => $this->totalViews,
            'monthGrid' => $monthGrid,
            'selectedDayDetails' => $selectedDayDetails,
            'allowedYears' => $this->allowedYears,
            'selectedYear' => $this->selectedYear,
            'selectedMonthNumber' => $this->selectedMonthNumber,
        ])->layout('components.layouts.public', [
            'title' => $this->hall->name.' — EventHub Pakistan',
            'metaDescription' => Str::limit(strip_tags($this->hall->description ?? ''), 155, '...'),
            'ogImage' => $this->activePhoto,
        ]);
    }
}
