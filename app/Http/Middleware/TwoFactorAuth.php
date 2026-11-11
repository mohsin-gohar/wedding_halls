<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        // Check if 2FA is enabled for this user
        if ($user->two_factor_enabled && ! $request->session()->get('2fa_verified')) {
            $intended = $request->fullUrl();

            return redirect()->route('2fa.verify', ['intended' => $intended])
                ->with('warning', 'Two-factor authentication required.');
        }

        return $next($request);
    }
}
