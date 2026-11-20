<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public', ['title' => 'Services — Riwaj Grand Pakistan'])]
class PublicServices extends Component
{
    public function render()
    {
        return view('livewire.public-services');
    }
}
