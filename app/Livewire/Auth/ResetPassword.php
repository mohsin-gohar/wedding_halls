<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth', ['title' => 'Reset Password — Riwaj Grand'])]
class ResetPassword extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public bool $resetComplete = false;

    protected $rules = [
        'email' => 'required|email|exists:users,email',
        'password' => 'required|string|min:8|confirmed',
        'token' => 'required|string',
    ];

    public function mount(?string $token = null, ?string $email = null): void
    {
        if ($token) {
            $this->token = $token;
        }
        if ($email) {
            $this->email = $email;
        }

        if (empty($this->token) || empty($this->email)) {
            $this->errorMessage = 'Invalid or expired password reset link. Please request a new one.';
        }
    }

    public function resetPassword(): void
    {
        $this->validate();

        // Livewire update payloads carry component state, not top-level
        // request fields. Pass validated component state explicitly so the
        // reset cannot silently fail (or be driven by crafted query params).
        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                if ($user->status === 'ACTIVE') {
                    Auth::login($user);
                }
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $this->resetComplete = true;
            $this->feedbackMessage = 'Your password has been reset successfully. You are now logged in.';

            $route = $this->portalHomeRouteAfterReset();

            $this->redirect($route, navigate: true);
        } else {
            $this->errorMessage = __($status);
        }
    }

    private function portalHomeRouteAfterReset(): string
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return 'admin.dashboard';
        }

        if ($user->isCustomer()) {
            return 'customer.dashboard';
        }

        return 'dashboard';
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
