<?php

namespace App\Livewire;

use App\Exceptions\ServiceBookingException;
use App\Models\ServiceBooking;
use App\Models\Tenant;
use App\Services\ServiceBookingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Vendor ERP — Service bookings board.
 *
 * Vendors operate service bookings created via the marketplace RFQ engine
 * (photography, catering, decoration, beauty, planning, transport…).
 * Hall-based `bookings` remain on the legacy board (untouched).
 */
#[Layout('components.layouts.app', ['title' => 'Service Bookings — Vendor ERP'])]
class VendorBookingsBoard extends Component
{
    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public int $totalBookings = 0;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || (! $user->isTenantOwner() && ! $user->isManager() && ! $user->hasPermissionTo('service-bookings.view'))) {
            abort(403, 'Unauthorized. Service bookings are restricted to vendor staff.');
        }
    }

    public function getBookingsProperty()
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403, 'Forbidden. You do not have an active vendor organization assigned.');
        }

        return ServiceBooking::query()
            ->with(['customer:id,name,email', 'vendorCategory:id,name'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function getStatsProperty(): array
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403, 'Forbidden. You do not have an active vendor organization assigned.');
        }

        $totals = ServiceBooking::query()
            ->where('tenant_id', $tenantId)
            ->get(['status', 'quoted_amount', 'paid_amount']);

        return [
            'total' => $totals->count(),
            'pending' => $totals->whereIn('status', ['PENDING_VENDOR', 'QUOTE_SENT'])->count(),
            'confirmed' => $totals->whereIn('status', ['ACCEPTED', 'CONFIRMED', 'IN_PROGRESS'])->count(),
            'revenue' => (float) $totals->whereNotIn('status', ['CANCELLED', 'DECLINED'])->sum('quoted_amount'),
        ];
    }

    public function confirmBooking(string $serviceBookingId): void
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403);

            return;
        }

        $booking = ServiceBooking::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $serviceBookingId)
            ->first();

        // (tenant-scoped lookup — no cross-tenant read)
        if (! $booking) {
            abort(404);
        }

        try {
            app(ServiceBookingService::class)->confirmServiceBooking($serviceBookingId, $tenantId);
            $this->feedbackMessage = 'Booking confirmed. Advance payment tracking enabled.';
        } catch (ServiceBookingException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function startBooking(string $serviceBookingId): void
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403);

            return;
        }

        $booking = ServiceBooking::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $serviceBookingId)
            ->first();

        if (! $booking) {
            abort(404);
        }

        try {
            app(ServiceBookingService::class)->startServiceBooking($serviceBookingId, $tenantId);
            $this->feedbackMessage = 'Event IN_PROGRESS started.';
        } catch (ServiceBookingException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function completeBooking(string $serviceBookingId): void
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403);

            return;
        }

        $booking = ServiceBooking::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $serviceBookingId)
            ->first();

        if (! $booking) {
            abort(404);
        }

        try {
            app(ServiceBookingService::class)->completeServiceBooking($serviceBookingId, $tenantId);
            $this->feedbackMessage = 'Service completed. Vendor deliverables + review flow can now proceed.';
        } catch (ServiceBookingException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function cancelBooking(string $serviceBookingId, ?string $reason = null): void
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403);

            return;
        }

        $booking = ServiceBooking::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $serviceBookingId)
            ->first();

        if (! $booking) {
            abort(404);
        }

        if (! $booking->isCancellable()) {
            // Business-rule refusal — surfaced inline, never a fake success.
            $this->errorMessage = 'This booking can no longer be cancelled.';

            return;
        }

        try {
            app(ServiceBookingService::class)->cancelServiceBooking($serviceBookingId, $reason, $tenantId);

            $this->feedbackMessage = 'Booking cancelled.';
        } catch (ServiceBookingException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.vendor-bookings-board')
            ->layout('components.layouts.app', [
                'title' => 'Service Bookings — Vendor ERP',
            ]);
    }
}
