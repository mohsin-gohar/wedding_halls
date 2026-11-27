<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        // Verification links are short-lived and signed. Checking the request
        // signature also prevents callers from invoking this Livewire action
        // with a copied deterministic token after the link has expired.
        if (! request()->hasValidSignature()) {
            $this->errorMessage = 'Invalid or expired verification link. Request a new one below.';

            return;
        }

        $token = $token ?? $this->token;
        $email = $email ?? $this->email;

        if (empty($token) || empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errorMessage = 'Invalid or expired verification link. Request a new one below.';

            return;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->errorMessage = 'Invalid or expired verification link. Request a new one below.';

            return;
        }

        $expectedToken = hash_hmac('sha256', $user->email.'|'.$user->id, config('app.key'));

        if (! hash_equals($expectedToken, $token)) {
            $this->errorMessage = 'Invalid or expired verification link. Request a new one below.';

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
        $email = strtolower(trim($this->email));
        $this->validate(['email' => ['required', 'email', 'max:255']]);

        $throttleKey = 'email_resend|'.$email.'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->errorMessage = "Too many requests. Please wait {$seconds} seconds.";

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        $user = User::where('email', $email)->first();

        // Keep the response identical for unknown, already-verified and
        // pending accounts so this endpoint cannot be used for enumeration.
        if ($user && ! $user->email_verified_at) {
            $token = hash_hmac('sha256', $user->email.'|'.$user->id, config('app.key'));
            $verificationUrl = URL::temporarySignedRoute(
                'verify.email',
                now()->addHour(),
                ['token' => $token, 'email' => $user->email]
            );

            Mail::send('emails.verify-email', [
                'user' => $user,
                'token' => $token,
                'verificationUrl' => $verificationUrl,
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Verify Your Email Address — Riwaj Grand');
            });
        }

        $this->feedbackMessage = 'If this address is registered and still needs verification, a verification email will be sent shortly.';
    }

    public function render()
    {
        return view('livewire.auth.verify-email');
    }
}
