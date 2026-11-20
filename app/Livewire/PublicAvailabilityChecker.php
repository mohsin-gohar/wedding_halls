<?php

namespace App\Livewire;

use App\Models\Hall;
use App\Services\HallAvailabilityService;
use App\Support\CalendarWindow;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public', ['title' => 'Check Hall Availability — Riwaj Grand Pakistan'])]
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
        $date = CalendarWindow::parseDate($dateStr);

        if (! $date || ! CalendarWindow::contains($date)) {
            return;
        }

        $this->selectedDate = $date->format('Y-m-d');
        $this->resetPage();
    }

    public function updatedSelectedDate(): void
    {
        $this->normalizeSelectedDate();
        $this->resetPage();
    }

    /**
     * Clamp client-supplied state to a real, upcoming date inside the booking
     * horizon. Rendering also calls this so a corrupted payload can never
     * trigger a parse error or expose an elapsed date.
     */
    private function normalizeSelectedDate(): Carbon
    {
        $date = CalendarWindow::parseDate($this->selectedDate);

        if ($date === null) {
            $date = Carbon::tomorrow();
        } elseif (! CalendarWindow::contains($date)) {
            $date = $date->lt(Carbon::today())
                ? Carbon::today()
                : CalendarWindow::lastMonth()->endOfMonth();
        }

        $this->selectedDate = $date->format('Y-m-d');

        return $date;
    }

    public function render()
    {
        $halls = Hall::publicVisible()
            ->with(['venue', 'tenant'])
            ->orderBy('name')
            ->paginate(12);
        $date = $this->normalizeSelectedDate();

        // One authoritative conflict engine for public display and booking writes.
        $dateString = $date->format('Y-m-d');
        $availability = app(HallAvailabilityService::class)->blockedSlotsForHalls(
            $halls->pluck('id')->all(),
            $dateString
        );
        $bookings = collect($availability['bookings']);

        // Quick date shortcuts (This Friday, This Saturday, This Sunday, Next Saturday)
        $now = Carbon::now();
        $thisFriday = $now->isFriday() ? $now->copy() : $now->copy()->next(Carbon::FRIDAY);
        $thisSaturday = $now->isSaturday() ? $now->copy() : $now->copy()->next(Carbon::SATURDAY);
        $thisSunday = $now->isSunday() ? $now->copy() : $now->copy()->next(Carbon::SUNDAY);
        $nextSaturday = $thisSaturday->copy()->addWeek();

        $shortcuts = [
            ['label' => 'Tomorrow', 'date' => Carbon::tomorrow()->format('Y-m-d')],
            ['label' => 'This Friday', 'date' => $thisFriday->format('Y-m-d')],
            ['label' => 'This Saturday', 'date' => $thisSaturday->format('Y-m-d')],
            ['label' => 'This Sunday', 'date' => $thisSunday->format('Y-m-d')],
            ['label' => 'Next Saturday', 'date' => $nextSaturday->format('Y-m-d')],
        ];

        return view('livewire.public-availability-checker', [
            'halls' => $halls,
            'date' => $date,
            'bookings' => $bookings,
            'shortcuts' => $shortcuts,
            'minDate' => Carbon::today()->format('Y-m-d'),
            'maxDate' => CalendarWindow::lastMonth()->endOfMonth()->format('Y-m-d'),
        ]);
    }
}
