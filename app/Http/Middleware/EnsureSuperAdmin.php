<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next)
    {
        // If user is not authenticated, redirect to login
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Please login to access the admin console.');
        }

        $user = Auth::user();

        // Check if user is super admin
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Redirect non-super admins away from admin routes
        return redirect()->route('home')
            ->with('error', 'You do not have access to the Super Admin console.');
    }
}