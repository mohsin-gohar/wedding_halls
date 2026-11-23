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
            // isRoleFamily() resolves enterprise aliases (ROLE_VENDOR_OWNER →
            // ROLE_TENANT_OWNER, ROLE_BOOKING_MANAGER → ROLE_FRONT_DESK, …)
            // and Spatie-assigned roles, so alias accounts are not bounced
            // between their guarded routes and an inaccessible portal home.
            if ($user->isRoleFamily($role)) {
                return $next($request);
            }
        }

        return redirect()->route($user->portalHomeRoute());
    }
}
