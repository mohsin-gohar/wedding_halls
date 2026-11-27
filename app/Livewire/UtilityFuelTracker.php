<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\UtilityLog;
use App\Support\PermissionMatrix;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

    private function authorizeUtilityAction(string $permission): void
    {
        $user = Auth::user();
        abort_unless($user && ! $user->isCustomer(), 403);
        abort_unless($user->isSuperAdmin() || $user->tenant_id, 403);
        abort_unless(PermissionMatrix::userCan($user, $permission), 403, 'Missing utility tracking permission.');
    }

    public function mount()
    {
        $this->authorizeUtilityAction('utilities.view');
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
        $this->authorizeUtilityAction('utilities.view');
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
        $this->authorizeUtilityAction('utilities.manage');
        $user = Auth::user();
        $bookingRule = Rule::exists('bookings', 'id')->whereIn('status', ['CONFIRMED', 'COMPLETED']);
        if (! $user->isSuperAdmin()) {
            $bookingRule->where('tenant_id', $user->tenant_id);
        }

        $this->validate([
            'selectedBookingId' => ['required', $bookingRule],
            'generator_run_hours' => 'required|numeric|min:0|max:99999.99',
            'diesel_liters_consumed' => 'required|numeric|min:0|max:999999.99',
            'diesel_rate_per_liter' => 'required|numeric|min:0|max:999999.99',
            'wapda_units_consumed' => 'required|numeric|min:0|max:999999.99',
            'wapda_unit_rate' => 'required|numeric|min:0|max:999999.99',
            'notes' => 'nullable|string|max:2000',
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

        $totalCost = round(
            ($this->diesel_liters_consumed * $this->diesel_rate_per_liter)
            + ($this->wapda_units_consumed * $this->wapda_unit_rate),
            2
        );

        if (! is_finite($totalCost) || $totalCost > 99999999.99) {
            $this->addError('diesel_liters_consumed', 'Combined utility cost exceeds the supported maximum for this event.');

            return;
        }

        if (! in_array($booking->status, ['CONFIRMED', 'COMPLETED'], true)) {
            $this->addError('selectedBookingId', 'Utility logs can only be attached to confirmed or completed events.');

            return;
        }

        DB::transaction(function () use ($booking, $totalCost): void {
            $user = auth()->user();
            $utility = UtilityLog::query()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->first();

            // The utility expense is source-linked: one expense row per booking
            // utility log, updated in place on re-save (no duplicates, no rewrites
            // of unrelated financial history).
            $expense = $utility?->expense;
            $category = ($this->diesel_liters_consumed > 0 || $this->generator_run_hours > 0)
                ? 'GENERATOR_DIESEL'
                : 'WAPDA_ELECTRICITY';

            $expenseData = [
                'tenant_id' => $booking->tenant_id,
                'category' => $category,
                'title' => 'Utility consumption — '.$booking->booking_reference,
                'amount' => $totalCost,
                'expense_date' => $booking->event_date ?? now()->toDateString(),
                'payment_method' => 'CASH',
                'notes' => $this->notes,
                'created_by_user_id' => $user?->id,
            ];

            if ($expense) {
                // Posted financial records are never silently rewritten. A
                // changed cost must be posted as an audited correction from
                // the expense ledger instead.
                if (abs((float) $expense->amount - $totalCost) > 0.009) {
                    $this->addError('diesel_liters_consumed', 'Utility cost changed. Post an audited expense correction from the expense ledger instead of rewriting the original.');

                    return;
                }
            } else {
                $expense = Expense::create($expenseData);
                AuditLog::log('EXPENSE_CREATED', 'Utility-linked expense PKR '.number_format($totalCost, 2).' created for booking '.$booking->booking_reference.'.', $booking->tenant_id, 'Expense', $expense->id);
            }

            $utilityData = [
                'tenant_id' => $booking->tenant_id,
                'expense_id' => $expense->id,
                'generator_run_hours' => $this->generator_run_hours,
                'diesel_liters_consumed' => $this->diesel_liters_consumed,
                'diesel_rate_per_liter' => $this->diesel_rate_per_liter,
                'wapda_units_consumed' => $this->wapda_units_consumed,
                'wapda_unit_rate' => $this->wapda_unit_rate,
                'total_utility_cost' => $totalCost,
                'logged_by' => $user->name ?? 'Banquet Manager',
                'notes' => $this->notes,
            ];

            if ($utility) {
                $utility->update($utilityData);
            } else {
                UtilityLog::create(['booking_id' => $booking->id] + $utilityData);
            }
        });

        $this->successMessage = 'Generator run-hours and fuel consumption logged successfully!';
    }

    public function render()
    {
        $this->authorizeUtilityAction('utilities.view');
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
