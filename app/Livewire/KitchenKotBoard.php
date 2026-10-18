<?php

namespace App\Livewire;

use App\Models\KitchenKot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class KitchenKotBoard extends Component
{
    public string $filterDate;

    public function mount()
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && (!$user->tenant_id || $user->isCustomer()))) {
            abort(403, 'Unauthorized. Culinary and operations staff access required.');
        }
        $this->filterDate = Carbon::today()->format('Y-m-d');
    }

    public function updateStatus(string $kotId, string $status)
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && (!$user->tenant_id || $user->isCustomer()))) {
            abort(403, 'Unauthorized.');
        }

        $query = KitchenKot::query();
        if (!$user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }
        $kot = $query->findOrFail($kotId);
        $kot->update(['preparation_status' => $status]);
        $this->dispatch('kotUpdated');
    }

    public function render()
    {
        $user = Auth::user();
        $tenantId = $user ? $user->tenant_id : null;
        $isSuperAdmin = $user && $user->isSuperAdmin();

        $kotsQuery = KitchenKot::with(['booking.hall'])
            ->whereHas('booking', function ($q) use ($tenantId, $isSuperAdmin) {
                $q->where('event_date', $this->filterDate);
                if (!$isSuperAdmin) {
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
