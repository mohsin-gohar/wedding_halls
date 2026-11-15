<?php

namespace App\Policies;

use App\Models\RFQ;
use App\Models\RFQBid;
use App\Models\User;

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
        if ($user->isTenantOwner()) {
            // Vendor can bid on RFQs targeting their category
            return true;
        }

        return false;
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
