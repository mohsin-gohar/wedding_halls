<?php

namespace App\Livewire;

use App\Models\PageVisit;
use App\Models\Review;
use App\Models\Tenant;
use App\Models\VendorCategory;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Public vendor profile page (any category) — premium SEO-friendly page with
 * portfolio, services/packages, verified reviews and a quote-request CTA.
 */
#[Layout('components.layouts.public', ['title' => 'Vendor Profile | Riwaj Grand'])]
class PublicVendorProfile extends Component
{
    public Tenant $vendor;

    public ?VendorCategory $category;

    public function mount(string $slug): void
    {
        $this->vendor = Tenant::query()
            ->where('slug', $slug)
            ->where('marketplace_visible', true)
            ->where('is_active', true)
            ->where('verification_status', 'APPROVED')
            ->with(['vendorCategory', 'portfolioMedia', 'vendorServices' => fn ($q) => $q->where('is_active', true)->orderBy('sort'), 'vendorPackages' => fn ($q) => $q->where('is_active', true)->orderBy('sort')])
            ->withCount(['approvedReviews as reviews_published_count'])
            ->firstOrFail();

        $this->category = $this->vendor->vendorCategory;

        $this->vendor->increment('profile_views');

        PageVisit::record(
            request()->path(),
            request()->route()?->getName(),
            auth()->id() ? (string) auth()->id() : null,
            request()->ip(),
            request()->userAgent() ?? ''
        );
    }

    public function getReviewsProperty()
    {
        $vendorId = $this->vendor->id;
        $hallIds = $this->vendor->halls()->pluck('id');

        return Review::query()
            ->where(function ($outer) use ($vendorId, $hallIds) {
                // New polymorphic reviews aimed directly at this vendor…
                $outer->where(function ($morph) use ($vendorId) {
                    $morph->where('reviewable_type', Tenant::class)
                        ->where('reviewable_id', $vendorId);
                });

                // …plus legacy/new hall reviews belonging to this hall vendor.
                if ($hallIds->isNotEmpty()) {
                    $outer->orWhereIn('hall_id', $hallIds);
                }
            })
            ->approved()
            ->with('user:id,name')
            ->latest()
            ->take(6)
            ->get();
    }

    public function getPortfolioProperty()
    {
        return $this->vendor->portfolioMedia;
    }

    public function render()
    {
        return view('livewire.public-vendor-profile')
            ->layout('components.layouts.public', [
                'title' => $this->vendor->name.' — '.($this->category?->name ?? 'Vendor').' | Riwaj Grand',
                'metaDescription' => Str::limit($this->vendor->about ?? '', 150),
            ]);
    }
}
