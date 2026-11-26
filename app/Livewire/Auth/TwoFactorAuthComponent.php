<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use PragmaRX\Google2FA\Google2FA;

/**
 * Two-factor authentication setup and verification.
 *
 * The TOTP secret is encrypted at rest, the recovery codes are stored hashed
 * and are single-use, and the setup QR code is drawn locally so the secret is
 * never sent to a third-party QR service.
 */
#[Layout('components.layouts.auth', ['title' => 'Two-Factor Authentication — Riwaj Grand'])]
class TwoFactorAuthComponent extends Component
{
    public string $code = '';

    public string $password = '';

    public string $intended = '/';

    public bool $setupMode = false;

    public string $secret = '';

    public string $qrCodeDataUri = '';

    public array $recoveryCodes = [];

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public function mount(?string $intended = null): void
    {
        $this->intended = $this->normalizeIntended($intended);

        $user = Auth::user();

        if ($user && ! $user->two_factor_enabled) {
            $this->setupMode = true;
            $this->generateSecret();
        }

        $this->qrCodeDataUri = $this->buildQrCodeDataUri();
    }

    /**
     * Only local relative paths may be used as a post-2FA redirect target.
     * A crafted `?intended=https://evil.example` must never become an open redirect.
     */
    private function normalizeIntended(?string $intended): string
    {
        if (! is_string($intended) || $intended === '') {
            return '/';
        }

        if (! str_starts_with($intended, '/') || str_starts_with($intended, '//') || str_contains($intended, '\\')) {
            return '/';
        }

        return $intended;
    }

    public function generateSecret(): void
    {
        $google2fa = new Google2FA;

        $this->secret = $google2fa->generateSecretKey();
        $this->recoveryCodes = $this->generateRecoveryCodes();
        $this->qrCodeDataUri = $this->buildQrCodeDataUri();
    }

    private function buildQrCodeDataUri(): string
    {
        $uri = '';

        try {
            $uri = $this->otpAuthUri();
        } catch (\Throwable) {
            return '';
        }

        if ($uri === '') {
            return '';
        }

        try {
            $result = (new Builder(
                writer: new SvgWriter,
                data: $uri,
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 320,
                margin: 12,
            ))->build();

            return 'data:image/svg+xml;base64,'.base64_encode($result->getString());
        } catch (\Throwable) {
            return '';
        }
    }

    private function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(Str::random(4).'-'.Str::random(4));
        }

        return $codes;
    }

    /**
     * The otpauth:// URI an authenticator app scans.
     */
    public function otpAuthUri(): string
    {
        $user = Auth::user();

        if (! $user || $this->secret === '') {
            return '';
        }

        return (new Google2FA)->getQRCodeUrl('Riwaj Grand', $user->email, $this->secret);
    }

    /**
     * The setup QR code data URI (public Livewire property, kept in sync by
     * the secret generator).
     */
    public function qrCodeSvgDataUri(): string
    {
        return $this->qrCodeDataUri;
    }

    public function enableTwoFactor(): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        $this->validate([
            'code' => 'required|string|min:6|max:6',
        ]);

        $google2fa = new Google2FA;

        if (! $google2fa->verifyKey($this->secret, $this->code)) {
            $this->errorMessage = 'Invalid verification code. Please try again.';

            return;
        }

        // Recovery codes are stored hashed — the plain values are shown once.
        $hashed = array_map(fn (string $plain) => hash('sha256', $plain), $this->recoveryCodes);

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => $this->secret,
            'two_factor_recovery_codes' => $hashed,
            'two_factor_confirmed_at' => now(),
        ]);

        session()->put('2fa_verified', true);

        $this->feedbackMessage = 'Two-factor authentication has been enabled successfully.';
        $this->errorMessage = null;
        $this->setupMode = false;
        $this->code = '';
    }

    public function verifyTwoFactor(): void
    {
        $this->validate([
            'code' => 'required|string|min:6|max:10',
        ]);

        $user = Auth::user();

        if (! $user || ! $user->two_factor_secret) {
            $this->errorMessage = 'Two-factor authentication is not configured for this account.';

            return;
        }

        $throttleKey = '2fa-verify:'.$user->id.'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->errorMessage = 'Too many verification attempts. Please try again in '
                .RateLimiter::availableIn($throttleKey).' seconds.';

            return;
        }

        $code = trim($this->code);

        // A recovery code (XXXX-XXXX) is accepted instead of the 6-digit TOTP.
        if (str_contains($code, '-')) {
            if ($this->consumeRecoveryCode($user, $code)) {
                RateLimiter::clear($throttleKey);
                session()->put('2fa_verified', true);
                $this->redirect($this->intended, navigate: true);

                return;
            }

            RateLimiter::hit($throttleKey, 600);
            $this->errorMessage = 'That recovery code is not valid or has already been used.';

            return;
        }

        $google2fa = new Google2FA;

        if ($google2fa->verifyKey($user->two_factor_secret, $code)) {
            RateLimiter::clear($throttleKey);
            session()->put('2fa_verified', true);
            $this->redirect($this->intended, navigate: true);
        } else {
            RateLimiter::hit($throttleKey, 600);
            $this->errorMessage = 'Invalid verification code. Please try again.';
        }
    }

    /**
     * Consume a single-use recovery code.
     */
    private function consumeRecoveryCode(User $user, string $plain): bool
    {
        $codes = $user->two_factor_recovery_codes ?: [];
        $hash = hash('sha256', $plain);

        if (! in_array($hash, $codes, true)) {
            return false;
        }

        $remaining = array_values(array_filter($codes, fn ($c) => ! hash_equals($c, $hash)));
        $user->update(['two_factor_recovery_codes' => $remaining]);

        return true;
    }

    public function disableTwoFactor(): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        // Disabling 2FA requires the current password — a stolen session must
        // not be enough to remove the second factor.
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($this->password, $user->password)) {
            $this->errorMessage = 'Your password is incorrect.';

            return;
        }

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $this->feedbackMessage = 'Two-factor authentication has been disabled.';
        $this->errorMessage = null;
        $this->setupMode = false;
        $this->code = '';
        $this->password = '';
    }

    public function render()
    {
        $user = Auth::user();
        $isEnabled = $user ? (bool) $user->two_factor_enabled : false;

        return view('livewire.auth.two-factor', [
            'isEnabled' => $isEnabled,
        ]);
    }
}
