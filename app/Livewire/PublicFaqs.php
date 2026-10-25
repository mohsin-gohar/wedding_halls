<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public', ['title' => 'Frequently Asked Questions — EventHub Pakistan'])]
class PublicFaqs extends Component
{
    public function render()
    {
        return view('livewire.public-faqs');
    }
}
