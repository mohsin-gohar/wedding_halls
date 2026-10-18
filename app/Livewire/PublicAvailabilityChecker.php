<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Hall;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public')]
class PublicAvailabilityChecker extends Component
{
    use WithPagination;

    public string $selectedDate = '';

    public function mount()
    {
        $this->selectedDate = Carbon::tomorrow()->format('Y-m-d');
    }

    public function setDate(string $dateStr)
    {
        if (! Carbon::hasFormat($dateStr, 'Y-m-d')) {
            return;
        }

        $this->selectedDate = $dateStr;
        $this->resetPage();
    }

    public function updatedSelectedDate(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $halls = Hall::with(['venue', 'tenant'])
            ->where('is_active', true)
            ->whereHas('tenant', function ($q) {
                $q->where('is_active', true)
                  ->where('verification_status', 'APPROVED');
            })
            ->orderBy('name')
            ->paginate(12);
        $date = Carbon::parse($this->selectedDate);

        // Fetch bookings for the selected date
        $bookingRows = Booking::whereDate('event_date', $this->selectedDate)
            ->whereIn('status', ['CONFIRMED', 'HOLD'])
            ->get(['id', 'hall_id', 'event_date', 'slot_type', 'status']);

        $bookings = collect();
        foreach ($bookingRows as $booking) {
            $slots = $booking->slot_type === 'FULL_DAY'
                ? ['AFTERNOON', 'EVENING', 'NIGHT']
                : [$booking->slot_type];

            foreach ($slots as $slot) {
                $bookings->put($booking->hall_id . '_' . $slot, $booking);
            }
        }

        // Quick date shortcuts (This Friday, This Saturday, This Sunday, Next Saturday)
        $shortcuts = [
            ['label' => 'Tomorrow', 'date' => Carbon::tomorrow()->format('Y-m-d')],
            ['label' => 'This Friday', 'date' => Carbon::now()->next(Carbon::FRIDAY)->format('Y-m-d')],
            ['label' => 'This Saturday', 'date' => Carbon::now()->next(Carbon::SATURDAY)->format('Y-m-d')],
            ['label' => 'This Sunday', 'date' => Carbon::now()->next(Carbon::SUNDAY)->format('Y-m-d')],
            ['label' => 'Next Saturday', 'date' => Carbon::now()->next(Carbon::SATURDAY)->addWeek()->format('Y-m-d')],
        ];

        return view('livewire.public-availability-checker', [
            'halls' => $halls,
            'date' => $date,
            'bookings' => $bookings,
            'shortcuts' => $shortcuts,
        ]);
    }
}
