<?php

namespace App\Livewire;

use App\Models\Tenant;
use App\Models\VendorCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Public multi-vendor marketplace directory.
 * Categories are DB-driven (vendor_categories) — adding a category in the
 * admin console automatically appears here with no code change.
 */
#[Layout('components.layouts.public', ['title' => 'Wedding Services Marketplace — Photographers, Caterers, Decorators | Riwaj Grand Pakistan'])]
class PublicVendors extends Component
{
    use WithPagination;

    #[Url]
    public string $category = 'all';

    #[Url]
    public string $city = 'All';

    #[Url]
    public string $search = '';

    #[Url]
    public string $budget = '';

    #[Url]
    public string $rating = '';

    #[Url]
    public string $sort = 'featured';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCity(): void
    {
        $this->resetPage();
    }

    public function updatingBudget(): void
    {
        $this->resetPage();
    }

    public function updatingRating(): void
    {
        $this->resetPage();
    }

    public function selectCategory(string $slug): void
    {
        $this->category = $slug;
        $this->resetPage();
    }

    /**
     * Top-level ancestor of the currently selected category slug.
     * Drives tab highlighting: picking a subcategory keeps its parent tab lit.
     */
    public function getActiveParentSlugProperty(): string
    {
        if ($this->category === 'all') {
            return 'all';
        }

        $categoryId = VendorCategory::query()->where('slug', $this->category)->value('id');

        if ($categoryId === null) {
            return 'all';
        }

        $parentId = VendorCategory::query()->where('id', $categoryId)->value('parent_id');

        if ($parentId === null) {
            return (string) VendorCategory::query()->where('id', $categoryId)->value('slug');
        }

        return (string) (VendorCategory::query()->where('id', $parentId)->value('slug') ?? 'all');
    }

    /**
     * Subcategory chips for the drill-down row — only shown while a
     * top-level category (or one of its children) is selected. When a
     * subcategory is active the row shows its siblings.
     */
    public function getSubcategoriesProperty()
    {
        $parentSlug = $this->activeParentSlug;

        if ($parentSlug === 'all') {
            return collect();
        }

        $parentId = VendorCategory::query()->where('slug', $parentSlug)->value('id');

        if ($parentId === null) {
            return collect();
        }

        return VendorCategory::query()
            ->where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    public function resetFilters(): void
    {
        $this->category = 'all';
        $this->city = 'All';
        $this->search = '';
        $this->budget = '';
        $this->rating = '';
        $this->sort = 'featured';
        $this->resetPage();
    }

    public function getCategoriesProperty()
    {
        // Public tabs show top-level categories only — subcategories live
        // inside each category and must not flood the marketplace filter bar.
        return VendorCategory::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort')
            ->withCount(['tenants' => fn ($q) => $q->marketplaceVisible()])
            ->get();
    }

    public function getCitiesProperty()
    {
        return Tenant::query()
            ->marketplaceVisible()
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
    }

    /** @var LengthAwarePaginator<int, Tenant> */
    public function getVendorsProperty()
    {
        $query = Tenant::query()
            ->marketplaceVisible()
            ->with('vendorCategory')
            ->withCount(['vendorServices as services_count' => fn ($q) => $q->where('is_active', true)]);

        if ($this->category !== 'all') {
            // Match vendors in the selected category AND in any of its
            // subcategories (businesses may register against either level).
            $selectedIds = VendorCategory::query()
                ->where('slug', $this->category)
                ->pluck('id');

            $categoryIds = $selectedIds->merge(
                VendorCategory::query()->whereIn('parent_id', $selectedIds)->pluck('id')
            );

            $query->whereIn('vendor_category_id', $categoryIds);
        }

        if ($this->city !== 'All') {
            $query->where('city', $this->city);
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(fn ($q) => $q
                ->where('name', 'like', $term)
                ->orWhere('about', 'like', $term)
                ->orWhereHas('vendorServices', fn ($s) => $s->where('name', 'like', $term)->where('is_active', true)));
        }

        // Budget slider buckets (per-head/entry price in PKR)
        if ($this->budget !== '') {
            $budgets = [
                'under-50k' => [0, 50000],
                '50k-150k' => [50000, 150000],
                '150k-300k' => [150000, 300000],
                '300k-plus' => [300000, null],
            ];
            [$min, $max] = $budgets[$this->budget] ?? [null, null];
            if ($min !== null) {
                $query->where(fn ($q) => $q
                    ->whereNull('min_budget')
                    ->orWhere('min_budget', '>=', $min));
            }
            if ($max !== null) {
                $query->where(fn ($q) => $q
                    ->whereNull('max_budget')
                    ->orWhere('max_budget', '<=', $max));
            }
        }

        if ($this->rating !== '') {
            $query->where('rating_avg', '>=', (float) $this->rating);
        }

        // Featured vendors always first, then sort preference.
        $sorts = [
            'featured' => fn ($q) => $q->orderByDesc('is_featured')->orderByDesc('rating_avg'),
            'rating' => fn ($q) => $q->orderByDesc('rating_avg')->orderByDesc('rating_count'),
            'name' => fn ($q) => $q->orderBy('name'),
            'newest' => fn ($q) => $q->orderByDesc('created_at'),
        ];

        ($sorts[$this->sort] ?? $sorts['featured'])($query);

        return $query->paginate(12);
    }

    public function render()
    {
        // Category-aware SEO: use the category's own seo_title/seo_description
        // when a filter is active, falling back to name/tagline, else the
        // marketplace default set on the layout attribute.
        $title = 'Wedding Services Marketplace — Photographers, Caterers, Decorators | Riwaj Grand Pakistan';
        $metaDescription = null;

        if ($this->category !== 'all') {
            $active = VendorCategory::query()->where('slug', $this->category)->first();

            if ($active) {
                $title = $active->seo_title
                    ?: $active->name.' in Pakistan — Verified Vendors | Riwaj Grand';
                $metaDescription = $active->seo_description
                    ?: $active->tagline
                    ?: $active->description;
            }
        }

        return view('livewire.public-vendors')
            ->layout('components.layouts.public', [
                'title' => $title,
                'metaDescription' => $metaDescription,
            ]);
    }
}
