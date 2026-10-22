<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\UtilityLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UtilityFuelTracker extends Component
{
    public ?string $selectedBookingId = null;

    public float $generator_run_hours = 3.5;

    public float $diesel_liters_consumed = 45.0;

    public float $diesel_rate_per_liter = 280.00;

    public float $wapda_units_consumed = 220.0;

    public float $wapda_unit_rate = 65.00;

    public ?string $notes = null;

    public ?string $successMessage = null;

    public function mount()
    {
        $user = Auth::user();
        if (! $user || (! $user->isSuperAdmin() && (! $user->tenant_id || $user->isCustomer()))) {
            abort(403, 'Unauthorized. Tenant staff access required.');
        }

        $query = Booking::whereIn('status', ['CONFIRMED', 'COMPLETED']);
        if (! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }
        $firstBooking = $query->latest()->first();

        if ($firstBooking) {
            $this->selectBooking($firstBooking->id);
        }
    }

    public function selectBooking(string $bookingId)
    {
        $user = Auth::user();
        if (! $user || (! $user->isSuperAdmin() && (! $user->tenant_id || $user->isCustomer()))) {
            abort(403, 'Unauthorized.');
        }

        $bookingQuery = Booking::where('id', $bookingId);
        if (! $user->isSuperAdmin()) {
            $bookingQuery->where('tenant_id', $user->tenant_id);
        }
        $targetBooking = $bookingQuery->first();
        if (! $targetBooking) {
            $this->selectedBookingId = null;

            return;
        }

        $this->selectedBookingId = $targetBooking->id;
        $this->successMessage = null;

        $logQuery = UtilityLog::where('booking_id', $bookingId);
        if (! $user->isSuperAdmin()) {
            $logQuery->where('tenant_id', $user->tenant_id);
        }
        $existingLog = $logQuery->first();
        if ($existingLog) {
            $this->generator_run_hours = (float) $existingLog->generator_run_hours;
            $this->diesel_liters_consumed = (float) $existingLog->diesel_liters_consumed;
            $this->diesel_rate_per_liter = (float) $existingLog->diesel_rate_per_liter;
            $this->wapda_units_consumed = (float) $existingLog->wapda_units_consumed;
            $this->wapda_unit_rate = (float) $existingLog->wapda_unit_rate;
            $this->notes = $existingLog->notes;
        }
    }

    public function saveUtilityLog()
    {
        $this->validate([
            'selectedBookingId' => 'required|exists:bookings,id',
            'generator_run_hours' => 'required|numeric|min:0',
            'diesel_liters_consumed' => 'required|numeric|min:0',
            'diesel_rate_per_liter' => 'required|numeric|min:0',
            'wapda_units_consumed' => 'required|numeric|min:0',
            'wapda_unit_rate' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        if (! $user || (! $user->isSuperAdmin() && (! $user->tenant_id || $user->isCustomer()))) {
            abort(403, 'Unauthorized.');
        }

        $bQuery = Booking::query();
        if (! $user->isSuperAdmin()) {
            $bQuery->where('tenant_id', $user->tenant_id);
        }
        $booking = $bQuery->findOrFail($this->selectedBookingId);

        $totalCost = ($this->diesel_liters_consumed * $this->diesel_rate_per_liter) +
                     ($this->wapda_units_consumed * $this->wapda_unit_rate);

        UtilityLog::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'tenant_id' => $booking->tenant_id,
                'generator_run_hours' => $this->generator_run_hours,
                'diesel_liters_consumed' => $this->diesel_liters_consumed,
                'diesel_rate_per_liter' => $this->diesel_rate_per_liter,
                'wapda_units_consumed' => $this->wapda_units_consumed,
                'wapda_unit_rate' => $this->wapda_unit_rate,
                'total_utility_cost' => round($totalCost, 2),
                'logged_by' => auth()->user()->name ?? 'Banquet Manager',
                'notes' => $this->notes,
            ]
        );

        $this->successMessage = 'Generator run-hours and fuel consumption logged successfully!';
    }

    public function render()
    {
        $user = Auth::user();
        $query = Booking::with(['hall', 'utilityLog'])
            ->whereIn('status', ['CONFIRMED', 'COMPLETED']);

        if ($user && $user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        } elseif (! $user || ! $user->isSuperAdmin()) {
            $query->whereRaw('1 = 0');
        }

        $bookings = $query->latest()->take(20)->get();

        $currentBookingQuery = Booking::with('hall');
        if ($user && $user->tenant_id && ! $user->isSuperAdmin()) {
            $currentBookingQuery->where('tenant_id', $user->tenant_id);
        } elseif (! $user || ! $user->isSuperAdmin()) {
            $currentBookingQuery->whereRaw('1 = 0');
        }
        $currentBooking = $this->selectedBookingId ? $currentBookingQuery->find($this->selectedBookingId) : null;

        $dieselCost = $this->diesel_liters_consumed * $this->diesel_rate_per_liter;
        $wapdaCost = $this->wapda_units_consumed * $this->wapda_unit_rate;
        $totalUtilityCost = $dieselCost + $wapdaCost;

        $estimatedNetProfit = 0.00;
        if ($currentBooking) {
            $estimatedFoodCost = ($currentBooking->guest_count * $currentBooking->per_head_rate) * 0.55;
            $estimatedNetProfit = max(0, $currentBooking->grand_total - $estimatedFoodCost - $totalUtilityCost);
        }

        return view('livewire.utility-fuel-tracker', [
            'bookings' => $bookings,
            'currentBooking' => $currentBooking,
            'dieselCost' => $dieselCost,
            'wapdaCost' => $wapdaCost,
            'totalUtilityCost' => $totalUtilityCost,
            'estimatedNetProfit' => $estimatedNetProfit,
        ]);
    }
}
