<?php

namespace App\Policies;

use App\Models\RFQ;
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
