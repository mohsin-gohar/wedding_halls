<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorPayout;

class VendorPayoutPolicy
{
    public function view(User $user, VendorPayout $payout): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isTenantOwner() || $user->isAccountant()) {
            return $payout->tenant_id === $user->tenant_id;
        }

        return false;
    }

    public function request(User $user): bool
    {
        return $user->isTenantOwner() || $user->isAccountant();
    }

    public function manage(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
