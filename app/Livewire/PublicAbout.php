<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public', ['title' => 'About Riwaj Grand Pakistan'])]
class PublicAbout extends Component
{
    public function render()
    {
        return view('livewire.public-about');
    }
}
