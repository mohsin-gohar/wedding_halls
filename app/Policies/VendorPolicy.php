<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use App\Support\PermissionMatrix;

class VendorPolicy
{
    public function view(User $user, Tenant $tenant): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id
            && (string) $user->tenant_id === (string) $tenant->id
            && PermissionMatrix::userCan($user, 'business.view');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id
            && (string) $user->tenant_id === (string) $tenant->id
            && PermissionMatrix::userCan($user, 'business.update');
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
