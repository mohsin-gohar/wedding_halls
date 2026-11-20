<?php

namespace App\Livewire;

use App\Models\Hall;
use App\Models\Venue;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public', ['title' => 'Venue Marketplace — Riwaj Grand Pakistan'])]
class PublicMarketplace extends Component
{
    use WithPagination;

    public string $city = 'All';

    public int $minCapacity = 100;

    public ?string $searchQuery = '';

    public function updatingCity(): void
    {
        $this->resetPage();
    }

    public function updatingMinCapacity(): void
    {
        $this->resetPage();
    }

    public function updatingSearchQuery(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Hall::publicVisible()
            ->whereHas('tenant', fn ($tenant) => $tenant->marketplaceVisible())
            ->with(['venue', 'tenant']);

        if ($this->city !== 'All') {
            $query->whereHas('venue', function ($q) {
                $q->where('city', $this->city);
            });
        }

        if ($this->minCapacity > 100) {
            $query->where('max_capacity', '>=', $this->minCapacity);
        }

        if ($this->searchQuery) {
            $searchTerm = '%'.trim($this->searchQuery).'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('halls.name', 'like', $searchTerm)
                    ->orWhereHas('venue', function ($venue) use ($searchTerm) {
                        $venue->where('name', 'like', $searchTerm)
                            ->orWhere('area', 'like', $searchTerm);
                    });
            });
        }

        $halls = $query->latest('halls.created_at')->latest('halls.id')->paginate(9);
        $cities = Venue::query()
            ->where('is_active', true)
            ->whereHas('halls', fn ($hall) => $hall->publicVisible())
            ->whereHas('tenant', fn ($tenant) => $tenant->marketplaceVisible())
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return view('livewire.public-marketplace', [
            'halls' => $halls,
            'cities' => $cities,
        ]);
    }
}
