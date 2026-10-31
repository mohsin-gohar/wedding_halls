<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_unless($user, 403);

        foreach ($roles as $role) {
            if ($user->role === $role || $user->hasRole($role)) {
                return $next($request);
            }
        }

        return redirect()->route($user->portalHomeRoute());
    }
}
