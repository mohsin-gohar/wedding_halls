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
        return VendorCategory::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->withCount(['tenants' => fn ($q) => $q
                ->where('marketplace_visible', true)
                ->where('is_active', true)
                ->where('verification_status', 'APPROVED')])
            ->get();
    }

    public function getCitiesProperty()
    {
        return Tenant::query()
            ->where('marketplace_visible', true)
            ->where('is_active', true)
            ->where('verification_status', 'APPROVED')
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
    }

    /** @var LengthAwarePaginator<int, Tenant> */
    public function getVendorsProperty()
    {
        $query = Tenant::query()
            ->where('marketplace_visible', true)
            ->where('is_active', true)
            ->where('verification_status', 'APPROVED')
            ->with('vendorCategory')
            ->withCount(['vendorServices as services_count' => fn ($q) => $q->where('is_active', true)]);

        if ($this->category !== 'all') {
            $query->whereHas('vendorCategory', fn ($q) => $q->where('slug', $this->category));
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
        return view('livewire.public-vendors');
    }
}
