<?php

namespace App\Livewire;

use App\Models\ServiceBooking;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Photographer Dashboard — shoot calendar, portfolio, deliverables, earnings.
 * Category: PHOTOGRAPHY
 */
#[Layout('components.layouts.app', ['title' => 'Photography Studio Dashboard — Riwaj Grand'])]
class PhotographerDashboard extends Component
{
    public function getTenantProperty(): ?Tenant
    {
        return auth()->user()->tenant;
    }

    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'total_bookings' => ServiceBooking::where('tenant_id', $tenantId)->count(),
            'upcoming' => ServiceBooking::where('tenant_id', $tenantId)
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

    public function getRecentBookingsProperty()
    {
        return ServiceBooking::where('tenant_id', auth()->user()->tenant_id)
            ->with('customer')
            ->latest()
            ->take(10)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.vendor-photographer-dashboard', [
            'stats' => $this->stats,
            'recentBookings' => $this->recentBookings,
        ]);
    }
}
