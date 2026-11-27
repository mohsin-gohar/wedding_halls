<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Default-deny every non-ACTIVE state, including PENDING and
        // unknown future statuses. A demoted session must not remain usable.
        if ($user && $user->status !== 'ACTIVE') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => $user->status === 'SUSPENDED'
                    ? 'This account is currently suspended. Contact your venue administrator.'
                    : 'This account is not active. Contact your venue administrator.',
            ]);
        }

        return $next($request);
    }
}
