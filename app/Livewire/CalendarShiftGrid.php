<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CalendarShiftGrid extends Component
{
    public string $currentMonth = '';

    public ?string $selectedVenueId = null;

    public ?string $selectedHallId = null;

    // Quick Details Modal
    public ?Booking $viewingBooking = null;

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
        $hallsQuery = $this->accessibleHalls();
        $firstHall = $hallsQuery->first();

        if ($firstHall) {
            $this->selectedHallId = $firstHall->id;
            $this->selectedVenueId = $firstHall->venue_id;
        }
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
    }

    public function nextMonth()
    {
        $this->currentMonth = $this->normalizedMonth()->addMonth()->format('Y-m');
    }

    public function goToToday()
    {
        $this->currentMonth = Carbon::now()->format('Y-m');
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

        $selectedHall = $this->selectedHallId
            ? $this->accessibleHalls()->whereKey($this->selectedHallId)->first()
            : null;

        // Fetch bookings strictly scoped to this tenant and hall
        $bookings = [];
        if ($selectedHall) {
            $bookingsList = Booking::where('tenant_id', $selectedHall->tenant_id)
                ->where('hall_id', $selectedHall->id)
                ->whereBetween('event_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->whereIn('status', ['CONFIRMED', 'HOLD', 'INQUIRY'])
                ->whereRaw('date(event_date) between ? and ?', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->get();

            foreach ($bookingsList as $b) {
                $date = $b->event_date->format('Y-m-d');
                $slots = strtoupper($b->slot_type) === 'FULL_DAY'
                    ? ['AFTERNOON', 'EVENING', 'NIGHT']
                    : [strtoupper($b->slot_type)];
                foreach ($slots as $slot) {
                    $bookings[$date.'_'.$slot] ??= $b;
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

        return view('livewire.calendar-shift-grid', [
            'venues' => $venues,
            'halls' => $halls,
            'selectedHall' => $selectedHall,
            'days' => $days,
            'bookings' => $bookings,
            'monthTitle' => $startOfMonth->format('F Y'),
        ]);
    }
}
