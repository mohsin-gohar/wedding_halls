<?php

namespace App\Livewire;

use App\Models\PageVisit;
use App\Models\Review;
use App\Models\Tenant;
use App\Models\VendorCategory;
use App\Services\ServiceBookingService;
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
            ->marketplaceVisible()
            ->where('slug', $slug)
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

    /**
     * Ids (prefixed s/p) of services and packages that carry a fixed price
     * and can therefore be booked instantly via the "Book Now" CTA. Items
     * without a fixed price keep the quote-only CTA.
     */
    public function getDirectBookableIdsProperty(): array
    {
        $service = app(ServiceBookingService::class);
        $mode = $this->category?->booking_mode ?? 'DATE_EVENT';
        $probe = [
            'guest_count' => $mode === 'PER_HEAD' ? 1 : null,
            'hours' => $mode === 'HOURLY' ? 1 : null,
        ];

        $ids = [];

        foreach ($this->vendor->vendorServices as $item) {
            if ($service->previewDirectPrice($item, $mode, $probe) !== null) {
                $ids[] = 's'.$item->id;
            }
        }

        foreach ($this->vendor->vendorPackages as $item) {
            if ($service->previewDirectPrice($item, $mode, $probe) !== null) {
                $ids[] = 'p'.$item->id;
            }
        }

        return $ids;
    }

    /**
     * Other marketplace-visible vendors in the same category (excluding this
     * one) — powers the "Similar vendors" rail at the bottom of the profile.
     */
    public function getSimilarVendorsProperty()
    {
        if (! $this->category) {
            return collect();
        }

        return Tenant::query()
            ->marketplaceVisible()
            ->where('id', '!=', $this->vendor->id)
            ->where('vendor_category_id', $this->category->id)
            ->orderByDesc('is_featured')
            ->orderByDesc('rating_avg')
            ->take(3)
            ->get(['id', 'slug', 'name', 'city', 'cover_image', 'rating_avg', 'rating_count', 'is_featured']);
    }

    public function render()
    {
        // Meta description: vendor's own blurb first, else the category's
        // SEO description/tagline, else a generic category-aware sentence.
        $metaDescription = trim((string) ($this->vendor->about ?? ''));

        if ($metaDescription === '') {
            $metaDescription = trim((string) ($this->category?->seo_description
                ?? $this->category?->tagline
                ?? ''));
        }

        if ($metaDescription === '') {
            $metaDescription = 'Hire '.$this->vendor->name
                .($this->category ? ' — verified '.$this->category->name : ' service')
                .' on Riwaj Grand. View portfolio, packages and verified reviews.';
        }

        return view('livewire.public-vendor-profile')
            ->layout('components.layouts.public', [
                'title' => $this->vendor->name.' — '.($this->category?->name ?? 'Vendor').' | Riwaj Grand',
                'metaDescription' => Str::limit($metaDescription, 160),
            ]);
    }
}
