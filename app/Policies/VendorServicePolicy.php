<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorService;
use App\Support\PermissionMatrix;

class VendorServicePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Public marketplace visibility
    }


    public function view(User $user, VendorService $service): bool
    {
        return true; // Public marketplace visibility
    }

    public function manage(User $user, VendorService $service): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id
            && (string) $service->tenant_id === (string) $user->tenant_id
            && PermissionMatrix::userCan($user, 'services.manage');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || PermissionMatrix::userCan($user, 'services.manage');
    }
}
