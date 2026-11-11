<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Venue;
use App\Models\ShiftDefinition;
use App\Models\VenueShift;
use Carbon\Carbon;

class EventDashboard extends Component
{
    public string $currentMonth = '';
    public ?string $selectedVenueId = null;
    public ?string $selectedShift = null;
    public array $shiftStatus = [];
    public bool $showCalendar = false;

    public function mount(): void
    {
        $user = Auth::user();
        $this->currentMonth = Carbon::now()->format('Y-m');
        
        if ($user->isSuperAdmin()) {
            $venues = Venue::where('is_active', true)->get();
        } elseif ($user->tenant_id) {
            $venues = Venue::where('tenant_id', $user->tenant_id)->where('is_active', true)->get();
        } else {
            $venues = Venue::where('is_active', true)->limit(5)->get();
        }
        
        $this->selectedVenueId = $venues->first()?->id;
        $this->refreshShiftStatus();
    }

    public function refreshShiftStatus(): void
    {
        if (! $this->selectedVenueId) {
            return;
        }
        
        $venue = Venue::find($this->selectedVenueId);
        if (! $venue) {
            return;
        }
        
        $this->shiftStatus = [];
        
        foreach (ShiftDefinition::active()->get() as $shiftDef) {
            $existingShift = $venue->venueShifts()
                ->where('event_date', Carbon::now()->format('Y-m-d'))
                ->where('shift_definition_id', $shiftDef->id)
                ->first();
            
            $isBooked = $existingShift && $existingShift->is_active;
            
            $this->shiftStatus[$shiftDef->slug] = [
                'name' => $shiftDef->name,
                'start_time' => $shiftDef->start_time->format('h:i A'),
                'end_time' => $shiftDef->end_time->format('h:i A'),
                'status' => $isBooked ? 'FULLY_BOOKED' : 'AVAILABLE',
                'label' => $shiftDef->name . ' (' . $shiftDef->start_time->format('h:i A') . ' - ' . $shiftDef->end_time->format('h:i A') . ')',
                'booking_link' => route('venue.shifts.book', ['venue' => $venue->id, 'shift' => $shiftDef->slug]),
            ];
        }
    }

    public function availableShifts(Date $date): array
    {
        if (! $this->selectedVenueId) {
            return [];
        }
        
        $venue = Venue::find($this->selectedVenueId);
        if (! $venue) {
            return [];
        }
        
        $shifts = [];
        
        foreach (ShiftDefinition::active()->get() as $shiftDef) {
            $existingShift = $venue->venueShifts()
                ->where('event_date', $date->format('Y-m-d'))
                ->where('shift_definition_id', $shiftDef->id)
                ->first();
            
            $isBooked = $existingShift && $existingShift->is_active;
            $capacityUsed = $existingShift ? $existingShift->booked_capacity ?? 0 : 0;
            $capacityTotal = $existingShift ? $existingShift->max_capacity : $venue->max_capacity;
            
            $utilization = $capacityTotal > 0 ? round(($capacityUsed / $capacityTotal) * 100) : 0;
            
            $status = 'AVAILABLE';
            if ($utilization >= 100) {
                $status = 'FULLY_BOOKED';
            } elseif ($utilization >= 80) {
                $status = 'PARTIALLY_BOOKED';
            } elseif ($utilization >= 50) {
                $status = 'LIMITED';
            }
            
            $shifts[$shiftDef->slug] = [
                'name' => $shiftDef->name,
                'start_time' => $shiftDef->start_time->format('h:i A'),
                'end_time' => $shiftDef->end_time->format('h:i A'),
                'status' => $status,
                'utilization' => $utilization,
                'capacity_used' => $capacityUsed,
                'capacity_total' => $capacityTotal,
                'label' => $shiftDef->name . ' (' . $shiftDef->start_time->format('h:i A') . ' - ' . $shiftDef->end_time->format('h:i A') . ') - ' . $status,
            ];
        }
        
        return $shifts;
    }

    public function bookShift(string $shiftSlug): void
    {
        // Redirect to booking flow
        // This would typically redirect to a booking page
        session(['selected_shift' => $shiftSlug]);
    }

    public function render()
    {
        return view('livewire.event-dashboard');
    }
}