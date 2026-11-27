<?php

namespace App\Livewire;

use App\Models\ContactInquiry;
use App\Models\PlatformTask;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public', ['title' => 'Contact Us — Riwaj Grand Pakistan'])]
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
        'city' => 'required|string|max:80',
        'eventDate' => 'nullable|date',
        'message' => 'required|string|min:10|max:2000',
    ];

    public function submitInquiry()
    {
        $throttleKey = 'contact-submit:'.request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('message', 'Too many inquiries submitted. Please wait a minute and try again.');

            return;
        }

        // Count malformed submissions too, and consume the limit before writes.
        RateLimiter::hit($throttleKey, 60);

        $this->validate();

        ContactInquiry::create([
            'name' => trim($this->name),
            'email' => trim($this->email),
            'phone' => trim($this->phone),
            'city' => trim($this->city),
            'event_date' => ! empty($this->eventDate) ? $this->eventDate : null,
            'message' => trim($this->message),
            'status' => 'NEW',
        ]);

        // Auto-create task for Super Admin follow-up
        try {
            PlatformTask::create([
                'title' => "Customer Inquiry: {$this->name} ({$this->city})",
                'category' => 'CUSTOMER_INQUIRY',
                'priority' => 'HIGH',
                'due_date' => now()->addDay(),
                'notes' => "Phone: {$this->phone} | Email: {$this->email} | Message: ".Str::limit($this->message, 120),
            ]);
        } catch (\Throwable $e) {
            // Task creation failure should not block user inquiry
        }

        $this->isSubmitted = true;
    }

    public function render()
    {
        return view('livewire.public-contact');
    }
}
