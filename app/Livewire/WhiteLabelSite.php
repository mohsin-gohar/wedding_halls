<?php

namespace App\Livewire;

use App\Models\Review;
use App\Models\Tenant;
use App\Models\VendorLocation;
use App\Models\VendorPortfolioMedia;
use App\Models\VendorService;
use App\Support\TenantBrand;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * White-label vendor microsite.
 *
 * Rendered at /site/{slug} (and automatically on the vendor's own
 * subdomain) so a business can publish a branded website with its own
 * colours, logo, catalogue and enquiry form.
 */
#[Layout('components.layouts.public', ['title' => 'Vendor Site — Riwaj Grand'])]
class WhiteLabelSite extends Component
{
    public string $slug = '';

    public function mount(string $slug): void
    {
        $this->slug = $slug;

        if (! $this->tenant) {
            abort(404, 'This business site is not available.');
        }
    }

    public function getTenantProperty(): ?Tenant
    {
        return Tenant::query()
            ->where('slug', $this->slug)
            ->where('is_active', true)
            ->first();
    }

    public function getBrandProperty(): ?TenantBrand
    {
        return $this->tenant ? TenantBrand::for($this->tenant) : null;
    }

    public function getServicesProperty()
    {
        if (! $this->tenant) {
            return collect();
        }

        return VendorService::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('is_active', true)
            ->orderBy('sort')
            ->get();
    }

    public function getPortfolioProperty()
    {
        if (! $this->tenant) {
            return collect();
        }

        return VendorPortfolioMedia::query()
            ->where('tenant_id', $this->tenant->id)
            ->orderBy('sort')
            ->limit(12)
            ->get();
    }

    public function getReviewsProperty()
    {
        if (! $this->tenant) {
            return collect();
        }

        return Review::query()
            ->where('reviewable_type', Tenant::class)
            ->where('reviewable_id', $this->tenant->id)
            ->where('status', 'approved')
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();
    }

    public function getLocationsProperty()
    {
        if (! $this->tenant) {
            return collect();
        }

        return VendorLocation::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->get();
    }

    public function render()
    {
        $brand = $this->brand;

        return view('livewire.white-label-site', [
            'brand' => $brand,
            'vendor' => $this->tenant,
        ])->layout('components.layouts.public', [
            'title' => ($brand?->name ?? 'Vendor').' — '.($brand?->tagline ?: 'Riwaj Grand Marketplace'),
        ]);
    }
}
