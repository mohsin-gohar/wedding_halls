<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class PublicPolicies extends Component
{
    public string $section = 'terms'; // terms, privacy, cancellation

    public function mount(string $section = 'terms')
    {
        $this->section = in_array($section, ['terms', 'privacy', 'cancellation']) ? $section : 'terms';
    }

    public function render()
    {
        return view('livewire.public-policies');
    }
}
