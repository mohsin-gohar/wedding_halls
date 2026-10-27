<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strict gate for the platform Super Admin console.
 *
 * Unlike the portal role middleware (which redirects users to their own
 * dashboard), this middleware returns a hard 403 so the existence of the
 * administrative area is never confirmed to unauthorised accounts.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->role === 'ROLE_SUPER_ADMIN' || $user->isSuperAdmin()),
            403,
            'Access denied. Super Admin privileges are required.'
        );

        return $next($request);
    }
}
