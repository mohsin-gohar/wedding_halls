<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\PageVisit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public')]
class PublicVenues extends Component
{
    use WithPagination;

    #[Url]
    public string $city = 'All';

    #[Url]
    public string $search = '';

    #[Url]
    public string $date = '';

    #[Url]
    public string $shift = 'ALL';

    #[Url]
    public int $minCapacity = 0;

    #[Url]
    public float $maxBudget = 0;

    #[Url]
    public string $sort = 'popular'; // popular, price_asc, price_desc, capacity_desc

    public bool $hasAc = false;
    public bool $hasGenerator = false;
    public bool $hasBridal = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCity()
    {
        $this->resetPage();
    }

    public function updatingDate()
    {
        $this->resetPage();
    }

    public function updatingShift()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->city = 'All';
        $this->search = '';
        $this->date = '';
        $this->shift = 'ALL';
        $this->minCapacity = 0;
        $this->maxBudget = 0;
        $this->sort = 'popular';
        $this->hasAc = false;
        $this->hasGenerator = false;
        $this->hasBridal = false;
        $this->resetPage();
    }

    public function render()
    {
        // 1. Strict SaaS Visibility: Only show active halls belonging to active, verified/approved tenants
        $query = Hall::with(['venue', 'tenant'])
            ->where('is_active', true)
            ->whereHas('tenant', function ($t) {
                $t->where('is_active', true)
                  ->where('verification_status', 'APPROVED');
            });

        // 2. Filter by city
        if ($this->city !== 'All' && !empty($this->city)) {
            $query->whereHas('venue', function ($q) {
                $q->where('city', $this->city);
            });
        }

        // 3. Search term (Hall name, description, venue complex name, area, city)
        if (!empty($this->search)) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm)
                  ->orWhereHas('venue', function ($v) use ($searchTerm) {
                      $v->where('name', 'like', $searchTerm)
                        ->orWhere('area', 'like', $searchTerm)
                        ->orWhere('city', 'like', $searchTerm);
                  });
            });
        }

        // 4. Capacity filter
        if ($this->minCapacity > 0) {
            $query->where('max_capacity', '>=', $this->minCapacity);
        }

        // 5. Budget filter
        if ($this->maxBudget > 0) {
            $query->where('base_rent', '<=', $this->maxBudget);
        }

        // 6. Facilities & Amenities
        if ($this->hasAc) {
            $query->where('has_air_conditioning', true);
        }
        if ($this->hasGenerator) {
            $query->where('has_generator_backup', true);
        }
        if ($this->hasBridal) {
            $query->where('has_bridal_room', true);
        }

        // 7. Date and Shift Availability Filters
        if (!empty($this->date)) {
            $bookingQuery = Booking::whereDate('event_date', $this->date)
                ->whereIn('status', ['HOLD', 'CONFIRMED']);

            if ($this->shift !== 'ALL') {
                $bookingQuery->where('slot_type', $this->shift);
                $bookedHallIds = $bookingQuery->pluck('hall_id');
                $query->whereNotIn('id', $bookedHallIds);
            }
        }

        // 8. Sorting
        switch ($this->sort) {
            case 'price_asc':
                $query->orderBy('base_rent', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('base_rent', 'desc');
                break;
            case 'capacity_desc':
                $query->orderBy('max_capacity', 'desc');
                break;
            case 'popular':
            default:
                $query->latest();
                break;
        }

        $halls = $query->paginate(9);

        // Track page visit for venues listing
        PageVisit::record(
            request()->path(),
            request()->route()->getName(),
            auth()->id() ? (string) auth()->id() : null,
            request()->ip(),
            request()->userAgent() ?? ''
        );

        return view('livewire.public-venues', [
            'halls' => $halls,
        ]);
    }
}
