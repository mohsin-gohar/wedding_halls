<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class CustomerLogin extends Component
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
            session()->flash('message', 'Welcome back! Signed in to your customer portal.');
            return redirect()->intended(route('customer.dashboard'));
        }

        $this->addError('email', 'These credentials do not match our registered records.');
    }

    public function render()
    {
        return view('livewire.auth.customer-login');
    }
}
