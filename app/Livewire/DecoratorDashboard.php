<?php

namespace App\Livewire;

use App\Models\ServiceBooking;
use App\Models\VendorService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Decorator Dashboard — design portfolio, quotes, setup calendar, materials.
 * Category: DECORATION
 */
#[Layout('components.layouts.app', ['title' => 'Decoration & Production Dashboard — Riwaj Grand'])]
class DecoratorDashboard extends Component
{
    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'total_bookings' => ServiceBooking::where('tenant_id', $tenantId)->count(),
            'upcoming_projects' => ServiceBooking::where('tenant_id', $tenantId)
                ->where('event_date', '>=', now()->toDateString())
                ->whereIn('status', ['ACCEPTED', 'CONFIRMED', 'IN_PROGRESS'])
                ->count(),
            'pending_quotes' => ServiceBooking::where('tenant_id', $tenantId)
                ->where('status', 'QUOTE_SENT')
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
        return view('livewire.vendor-decorator-dashboard', [
            'stats' => $this->stats,
            'services' => $this->activeServices,
        ]);
    }
}
