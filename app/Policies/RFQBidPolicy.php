<?php

namespace App\Policies;

use App\Models\RFQBid;
use App\Models\User;

class RFQBidPolicy
{
    public function view(User $user, RFQBid $bid): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isTenantOwner()) {
            return $bid->vendor_id === $user->id;
        }
        if ($user->isCustomer()) {
            return $bid->rfq->client_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isTenantOwner() || $user->isSuperAdmin();
    }

    public function update(User $user, RFQBid $bid): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isTenantOwner()) {
            return $bid->vendor_id === $user->id;
        }

        return false;
    }
}
