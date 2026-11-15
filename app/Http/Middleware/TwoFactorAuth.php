<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $intended = $request->fullUrl();

        return redirect()->route('2fa.verify', ['intended' => $intended])
            ->with('warning', 'Two-factor authentication required.');
    }
}
