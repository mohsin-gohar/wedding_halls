<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

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

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::clear($throttleKey);
            $user = Auth::user();

            if ($user->status === 'SUSPENDED') {
                Auth::logout();
                $this->addError('email', 'Your account has been suspended by the platform administrator.');
                return;
            }

            $user->recordLogin();

            session()->flash('message', "Welcome back, {$user->name}!");

            // Website Owner / Super Admin → Platform Admin Dashboard
            if ($user->role === 'ROLE_SUPER_ADMIN' || $user->isSuperAdmin()) {
                return redirect()->intended(route('admin.dashboard'));
            }

            // Customer / Guest Client → Customer Booking Dashboard
            if ($user->isCustomer()) {
                return redirect()->intended(route('customer.dashboard'));
            }

            // Hall Admin / Tenant Owner / Staff → Hall Operations Dashboard
            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($throttleKey, 60);

        $this->addError('email', 'These credentials do not match our registered records.');
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('components.layouts.auth', ['title' => 'Sign In — EventHub']);
    }
}
