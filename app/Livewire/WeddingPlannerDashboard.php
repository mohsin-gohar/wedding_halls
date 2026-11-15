<?php

namespace App\Livewire;

use App\Models\ServiceBooking;
use App\Models\VendorService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Wedding Planner Dashboard — active events, client workspace, vendor coordination.
 * Category: WEDDING_PLANNERS
 */
#[Layout('components.layouts.app', ['title' => 'Wedding Planning and Event Management Dashboard — Riwaj Grand'])]
class WeddingPlannerDashboard extends Component
{
    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'active_events' => ServiceBooking::where('tenant_id', $tenantId)
                ->whereIn('status', ['ACCEPTED', 'CONFIRMED', 'IN_PROGRESS'])
                ->count(),
            'completed_events' => ServiceBooking::where('tenant_id', $tenantId)
                ->where('status', 'COMPLETED')
                ->count(),
            'upcoming' => ServiceBooking::where('tenant_id', $tenantId)
                ->where('event_date', '>=', now()->toDateString())
                ->whereIn('status', ['ACCEPTED', 'CONFIRMED', 'IN_PROGRESS'])
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
        return view('livewire.vendor-planner-dashboard', [
            'stats' => $this->stats,
            'services' => $this->activeServices,
        ]);
    }
}
