<?php

namespace App\Livewire;

use App\Models\ServiceBooking;
use App\Models\VendorService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Caterer Dashboard — upcoming events, menus, guest counts, kitchen schedule.
 * Category: CATERING
 */
#[Layout('components.layouts.app', ['title' => 'Catering Operations Dashboard — Riwaj Grand'])]
class CatererDashboard extends Component
{
    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'total_bookings' => ServiceBooking::where('tenant_id', $tenantId)->count(),
            'upcoming_events' => ServiceBooking::where('tenant_id', $tenantId)
                ->where('event_date', '>=', now()->toDateString())
                ->whereIn('status', ['ACCEPTED', 'CONFIRMED', 'IN_PROGRESS'])
                ->count(),
            'total_guests' => ServiceBooking::where('tenant_id', $tenantId)
                ->whereIn('status', ['ACCEPTED', 'CONFIRMED', 'IN_PROGRESS'])
                ->sum('guest_count'),
            'revenue' => ServiceBooking::where('tenant_id', $tenantId)
                ->whereNotIn('status', ['CANCELLED', 'DECLINED'])
                ->sum('quoted_amount'),
        ];
    }

    public function getActiveServicesProperty()
    {
        return VendorService::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.vendor-caterer-dashboard', [
            'stats' => $this->stats,
            'services' => $this->activeServices,
        ]);
    }
}
