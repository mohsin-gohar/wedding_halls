<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PermissionMatrix;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared guardrails for the stateless API.
 *
 * Every API action must resolve an authenticated principal first. The
 * historical behaviour (skip the tenant filter when no user is present)
 * leaked every tenant's records, so authorization here is fail-closed by
 * construction.
 */
abstract class ApiController extends Controller
{
    /**
     * Resolve the authenticated API principal or abort with 401.
     */
    protected function apiUser(Request $request): User
    {
        $user = $request->user() ?? auth('api')->user() ?? auth()->user();

        abort_if(! $user, 401, 'Unauthenticated. A valid bearer token is required.');

        abort_if(in_array($user->status, ['SUSPENDED', 'INACTIVE', 'BLOCKED'], true), 403, 'This account is not active.');

        return $user;
    }

    /**
     * Apply the mandatory tenant / customer scope to a query.
     *
     * Super admins see everything; tenant staff see their own tenant; a
     * customer only ever sees their own records.
     */
    protected function scopeToPrincipal(Builder $query, User $user, string $tenantColumn = 'tenant_id', string $customerColumn = 'customer_id'): Builder
    {
        if ($user->isSuperAdmin() || $user->isPlatformFinance() || $user->isContentModerator()) {
            return $query;
        }

        if ($user->isCustomer()) {
            return $query->where($customerColumn, $user->id);
        }

        abort_if(! $user->tenant_id, 403, 'No vendor organisation is assigned to this account.');

        return $query->where($tenantColumn, $user->tenant_id);
    }

    /**
     * Ensure the principal may act on a record owned by the given tenant.
     */
    protected function assertTenantAccess(?string $tenantId, User $user): void
    {
        if ($user->isSuperAdmin() || $user->isPlatformFinance()) {
            return;
        }

        if ($user->isCustomer()) {
            abort(403, 'Customers cannot access tenant administration resources.');
        }

        abort_if(! $user->tenant_id, 403, 'No vendor organisation is assigned to this account.');
        abort_if($tenantId !== $user->tenant_id, 403, 'Cross-tenant access denied.');
    }

    /**
     * Server-side permission gate for API actions. Use the same canonical
     * role matrix as Livewire and navigation so a missing Spatie seed cannot
     * accidentally make a valid role fail closed or drift from UI policy.
     */
    protected function assertPermission(User $user, string $permission): void
    {
        abort_unless(PermissionMatrix::userCan($user, $permission), 403, 'Missing required permission: '.$permission);
    }
}
