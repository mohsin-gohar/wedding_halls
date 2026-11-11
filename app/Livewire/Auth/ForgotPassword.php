<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth', ['title' => 'Forgot Password — EventHub'])]
class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public bool $resetLinkSent = false;

    protected $rules = [
        'email' => 'required|email|exists:users,email',
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

        $status = Password::sendResetLink(
            request()->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            $this->resetLinkSent = true;
            $this->feedbackMessage = 'We have sent a password reset link to your email address. Please check your inbox.';
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
