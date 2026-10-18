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

        return redirect()->route($this->homeRoute($user->role));
    }

    private function homeRoute(?string $role): string
    {
        return match ($role) {
            'ROLE_SUPER_ADMIN' => 'admin.dashboard',
            'ROLE_ACCOUNTANT' => 'expenses',
            'ROLE_CHEF' => 'kitchen.board',
            'ROLE_FRONT_DESK', 'ROLE_FLOOR_STAFF' => 'calendar',
            default => 'dashboard',
        };
    }
}
