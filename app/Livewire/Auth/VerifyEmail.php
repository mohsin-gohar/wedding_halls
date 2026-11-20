<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth', ['title' => 'Verify Email — Riwaj Grand'])]
class VerifyEmail extends Component
{
    public string $email = '';

    public string $token = '';

    public bool $verified = false;

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public bool $resendAvailable = true;

    public function mount(?string $token = null, ?string $email = null): void
    {
        if ($token && $email) {
            $this->verifyEmail($token, $email);
        }
    }

    public function verifyEmail(?string $token = null, ?string $email = null): void
    {
        $token = $token ?? $this->token;
        $email = $email ?? $this->email;

        if (empty($token) || empty($email)) {
            $this->errorMessage = 'Invalid verification link.';

            return;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->errorMessage = 'User not found.';

            return;
        }

        $expectedToken = hash_hmac('sha256', $user->email.'|'.$user->id, config('app.key'));

        if (! hash_equals($expectedToken, $token)) {
            $this->errorMessage = 'Invalid or expired verification token.';

            return;
        }

        if ($user->email_verified_at) {
            $this->verified = true;
            $this->feedbackMessage = 'Your email is already verified. You can now log in.';

            return;
        }

        $user->update(['email_verified_at' => now()]);

        $this->verified = true;
        $this->feedbackMessage = 'Your email has been verified successfully! You can now log in.';
    }

    public function resendVerification(): void
    {
        $throttleKey = 'email_resend|'.strtolower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->errorMessage = "Too many requests. Please wait {$seconds} seconds.";

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        $user = User::where('email', $this->email)->first();

        if (! $user) {
            $this->errorMessage = 'User not found.';

            return;
        }

        if ($user->email_verified_at) {
            $this->feedbackMessage = 'Your email is already verified.';

            return;
        }

        $token = hash_hmac('sha256', $user->email.'|'.$user->id, config('app.key'));

        Mail::send('emails.verify-email', [
            'user' => $user,
            'token' => $token,
            'verificationUrl' => route('verify.email', ['token' => $token, 'email' => $user->email]),
        ], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Verify Your Email Address — Riwaj Grand');
        });

        $this->feedbackMessage = 'A new verification email has been sent to your inbox.';
    }

    public function render()
    {
        return view('livewire.auth.verify-email');
    }
}
