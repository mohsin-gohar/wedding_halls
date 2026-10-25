<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public', ['title' => 'About EventHub Pakistan'])]
class PublicAbout extends Component
{
    public function render()
    {
        return view('livewire.public-about');
    }
}
