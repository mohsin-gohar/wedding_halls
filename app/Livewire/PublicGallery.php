<?php

namespace App\Livewire;

use App\Models\Hall;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public', ['title' => 'Photo Gallery — Riwaj Grand Pakistan'])]
class PublicGallery extends Component
{
    public string $activeCategory = 'ALL'; // ALL, STAGES, LIGHTING, FLORAL, BANQUET, VIDEOS

    public function setCategory(string $category)
    {
        $this->activeCategory = $category;
    }

    public function render()
    {
        $halls = Hall::publicVisible()->with('venue')->get();

        return view('livewire.public-gallery', [
            'halls' => $halls,
        ]);
    }
}
