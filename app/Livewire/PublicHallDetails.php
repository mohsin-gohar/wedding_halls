<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\HallView;
use App\Models\HallWishlist;
use App\Models\Package;
use App\Models\PageVisit;
use Carbon\Carbon;
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
        $isUuid = \Illuminate\Support\Str::isUuid($identifier);
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
        $this->selectFirstBookableDateForCurrentMonth();
    }

    public int $monthOffset = 0; // 0 = current month, 1 = +1 month, 2 = +2 month

    public function prevMonth(): void
    {
        if ($this->monthOffset > 0) {
            $this->monthOffset--;
            $this->selectFirstBookableDateForCurrentMonth();
        }
    }

    public function nextMonth(): void
    {
        if ($this->monthOffset < 2) {
            $this->monthOffset++;
            $this->selectFirstBookableDateForCurrentMonth();
        }
    }

    public function setMonth(int $offset): void
    {
        if ($offset >= 0 && $offset <= 2) {
            $this->monthOffset = $offset;
            $this->selectFirstBookableDateForCurrentMonth();
        }
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
        if (! Carbon::hasFormat($date, 'Y-m-d')) {
            return;
        }

        $selectedDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        $today = Carbon::today();
        $targetMonth = $today->copy()->addMonths($this->monthOffset);

        if ($selectedDate->lt($today)
            || ! $selectedDate->isSameMonth($targetMonth)
            || ! $this->dateHasAvailableShift($selectedDate)) {
            return;
        }

        $this->selectedDate = $selectedDate->format('Y-m-d');
    }

    private function selectFirstBookableDateForCurrentMonth(): void
    {
        $today = Carbon::today();
        $targetMonth = $today->copy()->addMonths($this->monthOffset);
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

        $this->selectedDate = '';
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

        // Target month calculated by offset (0 = current, 1 = +1 month, 2 = +2 month)
        $targetDate = $today->copy()->addMonths($this->monthOffset);
        $monthTitle = $targetDate->format('F Y');

        $monthName = strtoupper($targetDate->format('F'));
        $year = $targetDate->format('Y');

        $startOfMonth = $targetDate->copy()->startOfMonth();
        $endOfMonth = $targetDate->copy()->endOfMonth();

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

        // Sunday-first weekday offset (0 = Sunday, 1 = Monday, ..., 6 = Saturday) to match requested calendar image
        $startDayOfWeek = $startOfMonth->dayOfWeek; // Carbon dayOfWeek: 0 (Sun) to 6 (Sat)

        // Query bookings for this full month
        $startDate = $startOfMonth->format('Y-m-d');
        $endDate = $endOfMonth->format('Y-m-d');

        $bookingRows = Booking::where('hall_id', $this->hall->id)
            ->whereBetween('event_date', [$startDate, $endDate])
            ->whereIn('status', ['CONFIRMED', 'HOLD'])
            ->select(['id', 'hall_id', 'event_date', 'slot_type', 'status', 'event_type'])
            ->get();

        // Expand legacy/full-day reservations so each of the three public shift cards
        // correctly shows as booked instead of exposing an overlapping booking CTA.
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
        ]);
    }
}
