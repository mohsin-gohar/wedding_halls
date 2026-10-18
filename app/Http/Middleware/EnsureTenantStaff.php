<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantStaff
{
    /**
     * Enforce strict tenant staff authorization for vendor ERP modules.
     * Customers and unassigned accounts must never access vendor operational tools.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super Admin has global oversight
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Customers must be isolated to their own customer portal
        if ($user->isCustomer()) {
            return redirect()->route('customer.dashboard')
                ->with('error', 'Access restricted. You are signed in as a client.');
        }

        // Vendor staff MUST have an assigned tenant_id
        if (!$user->tenant_id) {
            abort(403, 'Forbidden. You do not have an active venue organization assigned.');
        }

        return $next($request);
    }
}
