<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class VendorPolicy
{
    public function view(User $user, Tenant $tenant): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $tenant->id;
    }

    public function update(User $user, Tenant $tenant): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isTenantOwner() || $user->isManager()) {
            return $user->tenant_id === $tenant->id;
        }

        return false;
    }

    public function verify(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function suspend(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function feature(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function categoriesManage(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function documentsReview(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
