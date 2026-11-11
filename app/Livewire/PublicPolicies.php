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

        if ($section === 'refund') {
            $section = 'cancellation';
        }

        $this->section = in_array($section, ['terms', 'privacy', 'cancellation'], true) ? $section : 'terms';
    }

    public function render()
    {
        $titles = [
            'terms' => 'Terms & Conditions â€” Riwaj Grand Pakistan',
            'privacy' => 'Privacy Policy â€” Riwaj Grand Pakistan',
            'cancellation' => 'Cancellation & Refund Policy â€” Riwaj Grand Pakistan',
        ];

        return view('livewire.public-policies')->layout('components.layouts.public', [
            'title' => $titles[$this->section] ?? 'Policies â€” Riwaj Grand Pakistan',
        ]);
    }
}
