<?php

namespace App\Livewire;

use App\Models\Hall;
use App\Models\Venue;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class PublicMarketplace extends Component
{
    public string $city = 'All';
    public int $minCapacity = 100;
    public ?string $searchQuery = '';

    public function render()
    {
        $query = Hall::with(['venue', 'tenant'])->where('is_active', true);

        if ($this->city !== 'All') {
            $query->whereHas('venue', function ($q) {
                $q->where('city', $this->city);
            });
        }

        if ($this->minCapacity > 100) {
            $query->where('max_capacity', '>=', $this->minCapacity);
        }

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->searchQuery}%")
                  ->orWhereHas('venue', fn($vq) => $vq->where('name', 'like', "%{$this->searchQuery}%")->orWhere('area', 'like', "%{$this->searchQuery}%"));
            });
        }

        $halls = $query->get();
        $cities = Venue::where('is_active', true)->pluck('city')->unique();

        return view('livewire.public-marketplace', [
            'halls' => $halls,
            'cities' => $cities,
        ]);
    }
}
