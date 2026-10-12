<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class PublicContact extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $city = 'Lahore';

    public string $eventDate = '';

    public string $message = '';

    public bool $isSubmitted = false;

    protected $rules = [
        'name' => 'required|string|min:3|max:100',
        'email' => 'required|email|max:150',
        'phone' => 'required|string|min:10|max:25',
        'city' => 'required|string',
        'message' => 'required|string|min:10|max:2000',
    ];

    public function submitInquiry()
    {
        $this->validate();

        // In a production environment, send notification to concierge desk
        $this->isSubmitted = true;
    }

    public function render()
    {
        return view('livewire.public-contact');
    }
}
