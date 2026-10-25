<?php

namespace App\Livewire;

use App\Models\Hall;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public', ['title' => 'Photo Gallery — EventHub Pakistan'])]
class PublicGallery extends Component
{
    public string $activeCategory = 'ALL'; // ALL, STAGES, LIGHTING, FLORAL, BANQUET, VIDEOS

    public function setCategory(string $category)
    {
        $this->activeCategory = $category;
    }

    public function render()
    {
        $halls = Hall::with('venue')
            ->where('is_active', true)
            ->whereHas('tenant', function ($q) {
                $q->where('is_active', true)
                    ->where('verification_status', 'APPROVED');
            })
            ->get();

        return view('livewire.public-gallery', [
            'halls' => $halls,
        ]);
    }
}
