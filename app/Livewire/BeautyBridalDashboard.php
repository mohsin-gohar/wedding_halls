<?php

namespace App\Livewire;

use App\Models\ServiceBooking;
use App\Models\VendorService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Makeup Artist & Bridal Dashboard — appointments, packages, client bookings.
 * Category: BEAUTY_BRIDAL
 */
#[Layout('components.layouts.app', ['title' => 'Beauty and Bridal Studio Dashboard — Riwaj Grand'])]
class BeautyBridalDashboard extends Component
{
    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'total_bookings' => ServiceBooking::where('tenant_id', $tenantId)->count(),
            'upcoming_appointments' => ServiceBooking::where('tenant_id', $tenantId)
                ->where('event_date', '>=', now()->toDateString())
                ->whereIn('status', ['ACCEPTED', 'CONFIRMED', 'IN_PROGRESS'])
                ->count(),
            'completed' => ServiceBooking::where('tenant_id', $tenantId)
                ->where('status', 'COMPLETED')
                ->count(),
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
        return view('livewire.vendor-beauty-dashboard', [
            'stats' => $this->stats,
            'services' => $this->activeServices,
        ]);
    }
}
