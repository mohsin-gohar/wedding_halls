<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Hall;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class PublicAvailabilityChecker extends Component
{
    public string $selectedDate = '';

    public function mount()
    {
        $this->selectedDate = Carbon::tomorrow()->format('Y-m-d');
    }

    public function setDate(string $dateStr)
    {
        $this->selectedDate = $dateStr;
    }

    public function render()
    {
        $halls = Hall::where('is_active', true)
            ->whereHas('tenant', function ($q) {
                $q->where('is_active', true)
                    ->where('verification_status', 'APPROVED');
            })
            ->get();
        $date = Carbon::parse($this->selectedDate);

        // Fetch bookings for the selected date
        $bookings = Booking::whereDate('event_date', $this->selectedDate)
            ->whereIn('status', ['CONFIRMED', 'HOLD'])
            ->get()
            ->keyBy(function ($b) {
                return $b->hall_id.'_'.$b->slot_type;
            });

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
