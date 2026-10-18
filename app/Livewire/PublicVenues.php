<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\PageVisit;
use App\Models\Venue;
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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCity()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->city = 'All';
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        // 1. Strict SaaS Visibility: Only show active halls belonging to active, verified/approved tenants
        $publicHalls = Hall::with(['venue', 'tenant'])
            ->where('is_active', true)
            ->whereHas('venue', fn ($venueQuery) => $venueQuery->where('is_active', true))
            ->whereHas('tenant', function ($t) {
                $t->where('is_active', true)
                  ->where('verification_status', 'APPROVED');
            });

        $hasPublicHalls = (clone $publicHalls)->exists();
        $cities = Venue::query()
            ->where('is_active', true)
            ->whereHas('halls', function ($hallQuery) {
                $hallQuery->where('is_active', true)
                    ->whereHas('tenant', function ($tenantQuery) {
                        $tenantQuery->where('is_active', true)
                            ->where('verification_status', 'APPROVED');
                    });
            })
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->orderBy('city')
            ->distinct()
            ->pluck('city');

        $query = clone $publicHalls;

        // 2. Filter by city
        if ($this->city !== 'All' && !empty($this->city)) {
            $query->whereHas('venue', function ($q) {
                $q->where('city', $this->city);
            });
        }

        // Keep the directory search focused on venue names.
        if (!empty($this->search)) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where('name', 'like', $searchTerm);
        }

        $query->orderBy('name');

        // Count BEFORE pagination (pagination mutates the query)
        $totalHalls = (clone $query)->count();

        $halls = $query->paginate(9);

        $startDate = now()->toDateString();
        $endDate = now()->addDays(30)->toDateString();
        $bookingCounts = Booking::whereIn('status', ['HOLD', 'CONFIRMED'])
            ->whereBetween('event_date', [$startDate, $endDate])
            ->whereHas('hall', function ($hallQuery) {
                $hallQuery->where('is_active', true)
                    ->whereHas('tenant', function ($tenantQuery) {
                        $tenantQuery->where('is_active', true)
                            ->where('verification_status', 'APPROVED');
                    });
            })
            ->groupBy('event_date', 'slot_type')
            ->selectRaw('event_date, slot_type, COUNT(DISTINCT hall_id) as booked_count')
            ->get();

        $availability = [];
        for ($dayOffset = 1; $dayOffset <= 30; $dayOffset++) {
            $date = now()->addDays($dayOffset)->format('Y-m-d');
            $dayBookings = $bookingCounts->filter(fn ($booking) => $booking->event_date->format('Y-m-d') === $date);
            $fullDayCount = (int) ($dayBookings->firstWhere('slot_type', 'FULL_DAY')->booked_count ?? 0);

            $availability[$date] = [
                'afternoon' => (int) ($dayBookings->firstWhere('slot_type', 'AFTERNOON')->booked_count ?? 0) + $fullDayCount,
                'evening' => (int) ($dayBookings->firstWhere('slot_type', 'EVENING')->booked_count ?? 0) + $fullDayCount,
                'night' => (int) ($dayBookings->firstWhere('slot_type', 'NIGHT')->booked_count ?? 0) + $fullDayCount,
            ];
        }

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
            'availability' => $availability,
            'totalHalls' => $totalHalls,
            'cities' => $cities,
            'hasPublicHalls' => $hasPublicHalls,
        ]);
    }
}
