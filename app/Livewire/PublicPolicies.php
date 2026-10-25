<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class PublicPolicies extends Component
{
    public string $section = 'terms'; // terms, privacy, cancellation

    public function mount(?string $policy = null)
    {
        $section = $policy ?? request()->route('policy');

        if (! $section) {
            $last = basename(trim(request()->path(), '/'));
            if (in_array($last, ['terms', 'privacy', 'cancellation'], true)) {
                $section = $last;
            }
        }

        $this->section = in_array($section, ['terms', 'privacy', 'cancellation']) ? $section : 'terms';
    }

    public function render()
    {
        $titles = [
            'terms' => 'Terms & Conditions — EventHub Pakistan',
            'privacy' => 'Privacy Policy — EventHub Pakistan',
            'cancellation' => 'Cancellation & Refund Policy — EventHub Pakistan',
        ];

        return view('livewire.public-policies')->layout('components.layouts.public', [
            'title' => $titles[$this->section] ?? 'Policies — EventHub Pakistan',
        ]);
    }
}
