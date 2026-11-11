<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;
use PragmaRX\Google2FA\Google2FA;

#[Layout('components.layouts.auth', ['title' => 'Two-Factor Authentication â€” Riwaj Grand'])]
class TwoFactorAuthComponent extends Component
{
    public string $code = '';
    public string $intended = '/';
    public bool $setupMode = false;
    public string $secret = '';
    public array $recoveryCodes = [];
    public ?string $feedbackMessage = null;
    public ?string $errorMessage = null;

    public function mount(string $intended = null): void
    {
        $this->intended = $intended ?? '/';
        $user = Auth::user();

        if ($user && !$user->two_factor_enabled) {
            $this->setupMode = true;
            $this->generateSecret();
        }
    }

    public function generateSecret(): void
    {
        $google2fa = new Google2FA();
        $this->secret = $google2fa->generateSecretKey();
        $this->recoveryCodes = $this->generateRecoveryCodes();
    }

    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(\Illuminate\Support\Str::random(4).'-'.\Illuminate\Support\Str::random(4));
        }
        return $codes;
    }

    public function enableTwoFactor(): void
    {
        $user = Auth::user();

        $this->validate([
            'code' => 'required|string|min:6|max:6',
        ]);

        $google2fa = new Google2FA();

        if (! $google2fa->verifyKey($this->secret, $this->code)) {
        $this->errorMessage = 'Invalid verification code. Please try again.';
        return;
    }

    $user->update([
        'two_factor_enabled' => true,
        'two_factor_secret' => $this->secret,
    ]);

    session()->put('2fa_verified', true);

    $this->feedbackMessage = 'Two-factor authentication has been enabled successfully!';
    $this->setupMode = false;
    $this->code = '';
}

public function verifyTwoFactor(): void
{
    $this->validate([
        'code' => 'required|string|min:6|max:6',
    ]);

    $user = Auth::user();
    $google2fa = new Google2FA();

    if ($google2fa->verifyKey($user->two_factor_secret, $this->code)) {
        session()->put('2fa_verified', true);
        $this->redirect($this->intended, navigate: true);
    } else {
        $this->errorMessage = 'Invalid verification code. Please try again.';
    }
}

public function disableTwoFactor(): void
{
    $user = Auth::user();
    $user->update([
        'two_factor_enabled' => false,
        'two_factor_secret' => null,
    ]);

    $this->feedbackMessage = 'Two-factor authentication has been disabled.';
    $this->setupMode = false;
    $this->code = '';
}

public function render()
{
    $user = Auth::user();
    $isEnabled = $user ? $user->two_factor_enabled : false;

    return view('livewire.auth.two-factor', [
        'isEnabled' => $isEnabled,
    ]);
}
}