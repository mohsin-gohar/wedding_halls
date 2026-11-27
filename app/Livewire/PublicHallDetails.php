<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Hall;
use App\Models\HallView;
use App\Models\HallWishlist;
use App\Models\ListingInquiry;
use App\Models\Package;
use App\Models\PageVisit;
use App\Services\HallAvailabilityService;
use App\Support\CalendarWindow;
use App\Support\ShiftWindows;
use Carbon\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.public')]
class PublicHallDetails extends Component
{
    /**
     * Curated showcase photos used when a hall has not uploaded its own gallery yet.
     */
    private const FALLBACK_IMAGES = [
        'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=1200&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200&auto=format&fit=crop',
    ];

    #[Locked]
    public Hall $hall;

    public string $activePhoto = '';

    public bool $isWishlisted = false;

    public int $wishlistCount = 0;

    public int $totalViews = 0;

    public string $inquiryName = '';

    public string $inquiryPhone = '';

    public string $inquiryEmail = '';

    public string $inquiryMessage = '';

    public ?string $inquiryFeedback = null;

    public function mount(?string $slug = null, ?string $id = null)
    {
        $identifier = $slug ?? $id;

        if (! $identifier) {
            abort(404, 'Venue not found.');
        }

        // 1. Strict Visibility: Support resolving by slug (preferred) or UUID fallback
        $isUuid = Str::isUuid($identifier);
        $this->hall = Hall::publicVisible()
            ->with(['venue', 'tenant'])
            ->where(function ($q) use ($identifier, $isUuid) {
                $q->where('halls.slug', $identifier);
                if ($isUuid) {
                    $q->orWhere('halls.id', $identifier);
                }
            })
            ->firstOrFail();

        $this->activePhoto = ! empty($this->hall->cover_image)
            ? $this->hall->cover_image
            : $this->displayImages()[0];

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

    public function submitInquiry(): void
    {
        $key = 'listing-inquiry:'.request()->ip().':'.$this->hall->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('inquiryMessage', 'Too many inquiries. Try again in a minute.');

            return;
        }

        // Count every submission attempt, including invalid payloads, and
        // consume the limit before persistence to close concurrent bursts.
        RateLimiter::hit($key, 60);

        $this->validate([
            'inquiryName' => 'required|string|min:2|max:120',
            'inquiryPhone' => 'required|string|min:7|max:30',
            'inquiryEmail' => 'nullable|email|max:160',
            'inquiryMessage' => 'required|string|min:10|max:1000',
        ]);

        ListingInquiry::create([
            'tenant_id' => $this->hall->tenant_id,
            'hall_id' => $this->hall->id,
            'name' => $this->inquiryName,
            'phone' => $this->inquiryPhone,
            'email' => $this->inquiryEmail ?: null,
            'purpose' => 'BOOKING',
            'message' => $this->inquiryMessage,
            'status' => 'NEW',
        ]);

        AuditLog::log(
            'LISTING_INQUIRY',
            "Inquiry for [{$this->hall->name}] from {$this->inquiryName}.",
            $this->hall->tenant_id,
            'Hall',
            $this->hall->id
        );

        $this->reset('inquiryName', 'inquiryPhone', 'inquiryEmail', 'inquiryMessage');
        $this->inquiryFeedback = 'Your inquiry was sent to '.$this->hall->tenant->name.'.';
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

    /**
     * Photos actually shown in the public gallery: the hall's uploaded
     * gallery when present, otherwise the curated showcase fallbacks.
     * Used by both the view and setPhoto() so every visible thumbnail is
     * selectable (and no arbitrary URL can be injected).
     */
    public function displayImages(): array
    {
        $images = is_array($this->hall->images)
            ? array_values(array_filter($this->hall->images, fn ($img) => is_string($img) && $img !== ''))
            : [];

        if (! empty($images)) {
            return $images;
        }

        return self::FALLBACK_IMAGES;
    }

    public function setPhoto(string $url)
    {
        // Validate that the requested photo is actually part of this hall's gallery,
        // its cover image, or the curated fallback set the visitor can see.
        $allowed = $this->displayImages();
        if (! empty($this->hall->cover_image)) {
            $allowed[] = $this->hall->cover_image;
        }

        if (in_array($url, $allowed, true)) {
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

        $availability = app(HallAvailabilityService::class);
        $blockedByDate = $availability->monthBlockedSlots($this->hall, $startDate, $endDate);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $blocked = $blockedByDate[$date->toDateString()] ?? [];

            if (count($blocked) < count(ShiftWindows::BOOKABLE_KEYS)) {
                $this->selectedDate = $date->format('Y-m-d');

                return;
            }
        }

        $this->selectedDate = $startDate->format('Y-m-d');
    }

    private function dateHasAvailableShift(Carbon $date): bool
    {
        return count(app(HallAvailabilityService::class)->blockedSlotsForHall($this->hall, $date))
            < count(ShiftWindows::BOOKABLE_KEYS);
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

        $availability = app(HallAvailabilityService::class);
        $storedWindows = $this->hall->tenant?->shift_windows;

        // Build monthGrid from the same conflict engine used by BookingService.
        // One booking query covers the whole month; the grid is folded in memory.
        $blockedByDate = $availability->monthBlockedSlots($this->hall, $startOfMonth, $endOfMonth);
        $monthGrid = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dayCarbon = $startOfMonth->copy()->day($d);
            $dateStr = $dayCarbon->format('Y-m-d');
            $dayAvailability = $availability->dayAvailability($this->hall, $dateStr, $blockedByDate[$dateStr] ?? []);

            $monthGrid[$d] = array_merge([
                'day' => $d,
                'date' => $dateStr,
                'dayOfWeek' => $dayCarbon->dayOfWeek,
                'dayName' => $dayCarbon->format('D'),
                'isPast' => $dayCarbon->isPast() && ! $dayCarbon->isToday(),
                'isToday' => $dayCarbon->isToday(),
            ], $dayAvailability);
        }

        // Details for currently selected date
        $selDateStr = $this->selectedDate ?: $startOfMonth->format('Y-m-d');
        $selCarbon = Carbon::parse($selDateStr);
        $selBlocked = $blockedByDate[$selDateStr] ?? $availability->blockedSlotsForHall($this->hall, $selDateStr);

        $selectedDayDetails = [
            'date' => $selDateStr,
            'formattedDate' => $selCarbon->format('l, M jS'),
            'afternoon' => [
                'name' => 'AFTERNOON',
                'time' => ShiftWindows::labelForSlot($storedWindows, 'AFTERNOON'),
                'icon' => '☀️',
                'isBooked' => isset($selBlocked['AFTERNOON']),
            ],
            'evening' => [
                'name' => 'EVENING',
                'time' => ShiftWindows::labelForSlot($storedWindows, 'EVENING'),
                'icon' => '🌄',
                'isBooked' => isset($selBlocked['EVENING']),
            ],
            'night' => [
                'name' => 'NIGHT',
                'time' => ShiftWindows::labelForSlot($storedWindows, 'NIGHT'),
                'icon' => '🌙',
                'isBooked' => isset($selBlocked['NIGHT']),
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
            'packages' => $packages,
            'images' => $this->displayImages(),
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
            'title' => $this->hall->name.' — Riwaj Grand Pakistan',
            'metaDescription' => Str::limit(strip_tags($this->hall->description ?? ''), 155, '...'),
            'ogImage' => $this->activePhoto,
        ]);
    }
}
