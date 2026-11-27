<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth', ['title' => 'Create Account — Riwaj Grand'])]
class CustomerRegister extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected $rules = [
        'name' => 'required|string|min:3|max:100',
        'email' => 'required|email|unique:users,email',
        'phone' => 'required|string|min:10|max:25',
        'password' => 'required|string|min:8|confirmed',
    ];

    public function register()
    {
        $throttleKey = 'customer_register|'.request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('name', "Aap dobara se register koshish nahi kar saktay. Baraye meherbani {$seconds} seconds intezar karein. (Too many registration attempts. Please wait {$seconds} seconds.)");

            return;
        }
        RateLimiter::hit($throttleKey, 60);

        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => 'ROLE_CUSTOMER',
            'password' => Hash::make($this->password),
        ]);

        $user->assignRole('ROLE_CUSTOMER');

        Auth::login($user);
        session()->regenerate();
        session()->flash('message', 'Welcome to Riwaj Grand! Your customer account is ready.');

        return redirect()->route('customer.dashboard');
    }

    public function render()
    {
        return view('livewire.auth.customer-register');
    }
}
