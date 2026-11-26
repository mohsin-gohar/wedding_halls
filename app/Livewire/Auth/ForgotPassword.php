<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth', ['title' => 'Forgot Password — Riwaj Grand'])]
class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public bool $resetLinkSent = false;

    protected $rules = [
        'email' => 'required|email',
    ];

    public function sendResetLink(): void
    {
        $this->validate();

        $throttleKey = 'password_reset|'.strtolower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->errorMessage = "Too many reset requests. Please wait {$seconds} seconds and try again.";

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        // Livewire update payloads carry component state, not top-level
        // request fields. Pass the validated component state explicitly.
        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT || $status === Password::INVALID_USER) {
            // Never reveal whether the address exists (account enumeration).
            $this->resetLinkSent = true;
            $this->feedbackMessage = 'If an account exists for that email address, we have sent a password reset link. Please check your inbox.';
            $this->email = '';
        } else {
            $this->errorMessage = __($status);
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
