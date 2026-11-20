<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth', ['title' => 'Sign In — Riwaj Grand'])]
class CustomerLogin extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public int $loginRetryAfter = 0;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|string',
    ];

    public function login()
    {
        $this->validate();

        // Throttle authentication attempts: 5 tries per minute per email+IP.
        $throttleKey = Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->loginRetryAfter = (int) $seconds;

            throw ValidationException::withMessages([
                'email' => "Aap dobara se login koshish nahi kar saktay. Baraye meherbani {$seconds} seconds intezar karein. (Too many login attempts. Please try again in {$seconds} seconds.)",
            ]);
        }

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();
            $user = Auth::user();

            if ($user->status === 'SUSPENDED') {
                Auth::logout();
                $this->addError('email', 'Your account has been suspended by the platform administrator.');

                return;
            }

            $user->recordLogin();
            session()->forget('url.intended');
            session()->flash('message', 'Welcome back! Signed in to your portal.');

            return redirect()->to(route($user->portalHomeRoute()));
        }

        RateLimiter::hit($throttleKey, 60);

        $this->addError('email', 'These credentials do not match our registered records.');
    }

    public function setLoginRetryAfter(int $seconds)
    {
        $this->loginRetryAfter = max(0, $seconds);
    }

    public function clearLoginRetryAfter()
    {
        $this->loginRetryAfter = 0;
    }

    public function render()
    {
        return view('livewire.auth.customer-login');
    }
}
