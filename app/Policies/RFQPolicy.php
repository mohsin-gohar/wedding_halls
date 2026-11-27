<?php

namespace App\Policies;

use App\Models\RFQ;
use App\Models\User;
use App\Support\PermissionMatrix;

class RFQPolicy
{
    public function view(User $user, RFQ $rfq): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isCustomer()) {
            return $rfq->client_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function bid(User $user, RFQ $rfq): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        $tenantId = $user->tenant_id;

        if (! $tenantId || ! PermissionMatrix::userCan($user, 'rfq.bid')) {
            return false;
        }

        // A vendor may respond only to its own RFQ or one explicitly targeted
        // to its tenant. Category membership alone is not an invitation.
        return (string) $rfq->tenant_id === (string) $tenantId
            || $rfq->targets()->where('tenant_id', $tenantId)->exists();
    }

    public function award(User $user, RFQ $rfq): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isCustomer()) {
            return $rfq->client_id === $user->id;
        }

        return false;
    }

    public function close(User $user, RFQ $rfq): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isCustomer()) {
            return $rfq->client_id === $user->id;
        }

        return false;
    }
}
