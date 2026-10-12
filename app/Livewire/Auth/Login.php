<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
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

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            $user = Auth::user();

            session()->flash('message', "Welcome back, {$user->name}!");

            if ($user->role === 'ROLE_SUPER_ADMIN' || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
                return redirect()->intended(route('admin.dashboard'));
            }

            if ($user->role === 'ROLE_CUSTOMER') {
                return redirect()->intended(route('customer.dashboard'));
            }

            return redirect()->intended(route('dashboard'));
        }

        $this->addError('email', 'These credentials do not match our registered records.');
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('components.layouts.public', ['title' => 'Hall Owner Login — Wedding_Hall']);
    }
}
