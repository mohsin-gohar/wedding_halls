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
        $this->currentMonth = Carbon::now()->format('Y-m');

        $user = Auth::user();
        $tenantId = $user ? $user->tenant_id : null;

        $hallsQuery = Hall::where('is_active', true);
        if ($tenantId) {
            $hallsQuery->where('tenant_id', $tenantId);
        } else {
            $hallsQuery->whereRaw('1 = 0');
        }
        $firstHall = $hallsQuery->first();

        if ($firstHall) {
            $this->selectedHallId = $firstHall->id;
            $this->selectedVenueId = $firstHall->venue_id;
        }
    }

    public function updatedSelectedVenueId($venueId)
    {
        $user = Auth::user();
        $tenantId = $user ? $user->tenant_id : null;

        $query = Hall::where('venue_id', $venueId)->where('is_active', true);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        } else {
            $query->whereRaw('1 = 0');
        }
        $firstHall = $query->first();
        $this->selectedHallId = $firstHall ? $firstHall->id : null;
    }

    public function prevMonth()
    {
        $this->currentMonth = Carbon::parse($this->currentMonth.'-01')->subMonth()->format('Y-m');
    }

    public function nextMonth()
    {
        $this->currentMonth = Carbon::parse($this->currentMonth.'-01')->addMonth()->format('Y-m');
    }

    public function goToToday()
    {
        $this->currentMonth = Carbon::now()->format('Y-m');
    }

    public function openWalkInModal(?string $date = null, ?string $slot = null)
    {
        $this->dispatch('openWalkInModal', [
            'hall_id' => $this->selectedHallId,
            'event_date' => $date,
            'slot_type' => $slot,
        ]);
    }

    public function viewBookingDetails(string $bookingId)
    {
        $user = Auth::user();
        $tenantId = $user ? $user->tenant_id : null;

        $query = Booking::with(['hall.venue', 'package', 'payments', 'kitchenKot']);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        } else {
            $query->whereRaw('1 = 0');
        }
        $this->viewingBooking = $query->find($bookingId);
        $this->showDetailsModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->viewingBooking = null;
    }

    public function render()
    {
        $user = Auth::user();
        $tenantId = $user ? $user->tenant_id : null;

        $venuesQuery = Venue::where('is_active', true);
        $hallsQuery = Hall::where('is_active', true);

        if ($tenantId) {
            $venuesQuery->where('tenant_id', $tenantId);
            $hallsQuery->where('tenant_id', $tenantId);
        } else {
            $venuesQuery->whereRaw('1 = 0');
            $hallsQuery->whereRaw('1 = 0');
        }

        $venues = $venuesQuery->get();

        if ($this->selectedVenueId) {
            $hallsQuery->where('venue_id', $this->selectedVenueId);
        }
        $halls = $hallsQuery->get();

        if (empty($this->currentMonth)) {
            $this->currentMonth = Carbon::now()->format('Y-m');
        }

        $startOfMonth = Carbon::parse($this->currentMonth.'-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $selectedHall = $tenantId && $this->selectedHallId ? Hall::where('tenant_id', $tenantId)->find($this->selectedHallId) : null;

        // Fetch bookings strictly scoped to this tenant and hall
        $bookings = [];
        if ($this->selectedHallId && $tenantId) {
            $bookingsList = Booking::where('tenant_id', $tenantId)
                ->where('hall_id', $this->selectedHallId)
                ->whereBetween('event_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->whereIn('status', ['CONFIRMED', 'HOLD', 'INQUIRY'])
                ->get();

            foreach ($bookingsList as $b) {
                $bookings[$b->event_date.'_'.$b->slot_type] = $b;
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
