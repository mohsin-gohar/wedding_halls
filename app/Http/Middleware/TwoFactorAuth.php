<?php

namespace App\Http\Middleware;

use App\Livewire\Auth\TwoFactorAuthComponent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Enforces two-factor authentication for accounts that have enabled it.
 *
 * Registered on the `web` middleware group so enabling 2FA on an account
 * actually gates its session at the HTTP layer (it was previously written
 * but never applied).
 */
class TwoFactorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->two_factor_enabled) {
            return $next($request);
        }

        if ($request->session()->get('2fa_verified')) {
            return $next($request);
        }

        // The challenge, its setup flow, sign-out and the credential routes
        // must stay reachable — otherwise the user could never clear the
        // challenge (redirect loop).
        if ($request->is('2fa', '2fa/*', 'logout', 'password', 'password/*', 'login', 'customer/login')) {
            return $next($request);
        }

        // Livewire performs component actions through a single XHR endpoint.
        // The challenge itself is a Livewire component, so its verification
        // update must pass through — otherwise this middleware redirects the
        // XHR and the user can never submit a code (a hard lockout). We allow
        // ONLY the TwoFactorAuthComponent's verify call; every other component
        // action (and the setup/disable methods that could replace the secret)
        // stays gated, so this is not a 2FA bypass.
        if ($this->isTwoFactorVerificationUpdate($request)) {
            return $next($request);
        }

        $intended = $request->fullUrl();

        return redirect()->route('2fa.verify', ['intended' => $intended])
            ->with('warning', 'Two-factor authentication required.');
    }

    private function isTwoFactorVerificationUpdate(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        $path = $request->path();

        $isLivewireUpdate = (is_string($routeName) && str_ends_with($routeName, 'livewire.update'))
            || (str_starts_with($path, 'livewire-') && str_ends_with($path, '/update'));

        if (! $isLivewireUpdate) {
            return false;
        }

        $components = $request->input('components');

        if (! is_array($components) || $components === []) {
            return false;
        }

        // Only the code submission and Livewire's own bookkeeping may run
        // while the challenge is outstanding. Setup/disable/secret rotation
        // are deliberately excluded.
        $allowedMethods = ['verifyTwoFactor', '$commit', '$refresh', '__dispatch'];

        foreach ($components as $component) {
            if (! is_array($component)) {
                return false;
            }

            $snapshot = $component['snapshot'] ?? null;
            $calls = $component['calls'] ?? null;

            if (! is_string($snapshot) || ! is_array($calls)) {
                return false;
            }

            try {
                $decoded = json_decode($snapshot, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                return false;
            }

            $name = $decoded['memo']['name'] ?? null;

            if (! is_string($name) || $name === '') {
                return false;
            }

            try {
                $class = app('livewire.factory')->resolveComponentClass($name);
            } catch (Throwable) {
                return false;
            }

            if (! is_a($class, TwoFactorAuthComponent::class, true)) {
                return false;
            }

            foreach ($calls as $call) {
                $method = is_array($call) ? ($call['method'] ?? null) : null;

                if (! is_string($method) || ! in_array($method, $allowedMethods, true)) {
                    return false;
                }
            }
        }

        return true;
    }
}
