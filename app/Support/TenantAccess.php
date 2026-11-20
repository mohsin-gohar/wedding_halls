<?php

namespace App\Support;

use App\Models\Tenant;

/**
 * Single source of truth for tenant-level access suspension.
 *
 * Subscription expiry is enforced in the request lifecycle (web + API), not
 * only in the nightly scheduler. Platform administrators are intentionally
 * exempt so they can still restore a locked account.
 */
class TenantAccess
{
    /**
     * Suspended/archived verification states are authoritative lock states;
     * subscription locks are delegated to SubscriptionService.
     */
    public static function isLocked(?Tenant $tenant): bool
    {
        if (! $tenant) {
            return false;
        }

        if (in_array($tenant->verification_status, ['SUSPENDED', 'BLOCKED'], true)) {
            return true;
        }

        return $tenant->isLocked();
    }

    public static function lockMessage(): string
    {
        return 'This venue account is locked. Contact the platform administrator.';
    }
}
