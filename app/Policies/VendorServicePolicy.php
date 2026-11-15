<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorService;

class VendorServicePolicy
{
    public function view(User $user, VendorService $service): bool
    {
        return true; // Public marketplace visibility
    }

    public function manage(User $user, VendorService $service): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isTenantOwner() || $user->isManager()) {
            return $service->tenant_id === $user->tenant_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isTenantOwner() || $user->isManager() || $user->isSuperAdmin();
    }
}
