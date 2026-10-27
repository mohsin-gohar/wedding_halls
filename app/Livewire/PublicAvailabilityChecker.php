<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Hall;
use App\Support\CalendarWindow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public', ['title' => 'Check Hall Availability — EventHub Pakistan'])]
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
        $halls = Hall::with(['venue', 'tenant'])
            ->where('is_active', true)
            ->whereHas('tenant', function ($q) {
                $q->where('is_active', true)
                    ->where('verification_status', 'APPROVED');
            })
            ->orderBy('name')
            ->paginate(12);
        $date = $this->normalizeSelectedDate();

        // Fetch bookings for the selected date. The result is cached for one
        // minute and keyed by the latest booking mutation so newly confirmed
        // reservations are reflected immediately.
        $dateString = $date->format('Y-m-d');
        $bookingStamp = md5((string) Booking::max('updated_at').'|'.Booking::count());
        $bookingRows = Cache::remember(
            "public:availability:{$dateString}:{$bookingStamp}",
            now()->addMinute(),
            fn () => Booking::whereDate('event_date', $dateString)
                ->whereIn('status', ['CONFIRMED', 'HOLD'])
                ->get(['id', 'hall_id', 'event_date', 'slot_type', 'status'])
        );

        $bookings = collect();
        foreach ($bookingRows as $booking) {
            $slots = $booking->slot_type === 'FULL_DAY'
                ? ['AFTERNOON', 'EVENING', 'NIGHT']
                : [$booking->slot_type];

            foreach ($slots as $slot) {
                $bookings->put($booking->hall_id.'_'.$slot, $booking);
            }
        }

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
