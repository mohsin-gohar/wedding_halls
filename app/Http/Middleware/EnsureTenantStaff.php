<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnsureTenantStaff
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
        // If user is not authenticated, skip tenant check
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $tenant = $user->tenant;

        // Super admin can access everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Tenant owner can access their own tenant resources
        if ($user->isTenantOwner()) {
            // Allow access to tenant-specific routes
            return $next($request);
        }

        // Check if the request is tenant-specific
        $tenantId = $request->route('tenant_id') ?? $request->query('tenant_id');

        // If no tenant ID in route/query, and user has a tenant, proceed
        if ($tenantId && $tenantId !== $tenant->id) {
            // User is trying to access another tenant's resources
            // Log the attempt
            Log::warning("Tenant access attempt: user {$user->id} trying to access tenant {$tenantId}", [
                'user_id' => $user->id,
                'attempted_tenant' => $tenantId,
                'user_tenant' => $tenant->id,
            ]);

            // Redirect or abort - for now, just continue but mark in logs
            // return abort(403, 'Unauthorized access to another tenant\'s resources.');
        }

        // Check if this is a staff-specific route that requires tenant context
        $action = $request->route()->action('uses') ?? '';

        // Staff can only access their own tenant's data
        if ($user->canManageHall() && !$user->isTenantOwner()) {
            // Manager/Accountant/Chef - they manage within their tenant
            if ($tenantId && $tenantId !== $tenant->id) {
                return abort(403, 'You do not have access to this tenant\'s resources.');
            }
        }

        return $next($request);
    }
}