<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\KitchenKot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class KitchenKotBoard extends Component
{
    public string $filterDate;

    public function mount()
    {
        $user = Auth::user();
        if (! $user || (! $user->isSuperAdmin() && (! $user->tenant_id || $user->isCustomer()))) {
            abort(403, 'Unauthorized. Culinary and operations staff access required.');
        }

        $this->filterDate = Carbon::today()->format('Y-m-d');

        if (! config('platform.kitchen_module_enabled')) {
            $this->redirect(route($user->portalHomeRoute()), navigate: true);
        }
    }

    public function updateStatus(string $kotId, string $status)
    {
        $user = Auth::user();
        if (! $user || (! $user->isSuperAdmin() && (! $user->tenant_id || $user->isCustomer()))) {
            abort(403, 'Unauthorized.');
        }

        // Never trust a client-supplied status string: validate against the
        // same enum as the database column, then enforce forward transitions.
        validator(['status' => $status], [
            'status' => ['required', Rule::in(KitchenKot::STATUSES)],
        ])->validate();

        DB::transaction(function () use ($kotId, $status, $user) {
            $query = KitchenKot::query();
            if (! $user->isSuperAdmin()) {
                $query->where('tenant_id', $user->tenant_id);
            }
            $kot = $query->whereKey($kotId)->lockForUpdate()->firstOrFail();

            if (! $kot->canTransitionTo($status)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot change KOT [{$kot->kot_number}] from {$kot->preparation_status} to {$status}.",
                ]);
            }

            $previousStatus = $kot->preparation_status;
            $kot->update(['preparation_status' => $status]);

            AuditLog::log(
                'KOT_STATUS_CHANGED',
                "Kitchen ticket [{$kot->kot_number}] moved from {$previousStatus} to {$status}.",
                $kot->tenant_id,
                'KitchenKot',
                $kot->id
            );
        });

        $this->dispatch('kotUpdated');
    }

    public function render()
    {
        $user = Auth::user();
        $tenantId = $user ? $user->tenant_id : null;
        $isSuperAdmin = $user && $user->isSuperAdmin();

        $kotsQuery = KitchenKot::with(['booking.hall'])
            ->whereHas('booking', function ($q) use ($tenantId, $isSuperAdmin) {
                $q->whereDate('event_date', $this->filterDate);
                if (! $isSuperAdmin) {
                    if ($tenantId) {
                        $q->where('tenant_id', $tenantId);
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                }
            });

        $kots = $kotsQuery->latest()->get();

        // Calculate totals for the selected day
        $totalMeat = $kots->sum('raw_meat_kg');
        $totalRice = $kots->sum('raw_rice_kg');
        $totalOil = $kots->sum('raw_oil_liters');
        $totalGuests = $kots->sum('guest_count');

        return view('livewire.kitchen-kot-board', [
            'kots' => $kots,
            'totalMeat' => $totalMeat,
            'totalRice' => $totalRice,
            'totalOil' => $totalOil,
            'totalGuests' => $totalGuests,
        ]);
    }
}
