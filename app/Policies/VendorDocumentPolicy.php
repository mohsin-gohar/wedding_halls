<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorDocument;

class VendorDocumentPolicy
{
    public function view(User $user, VendorDocument $document): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isTenantOwner()) {
            return $document->tenant_id === $user->tenant_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isTenantOwner();
    }

    public function update(User $user, VendorDocument $document): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isTenantOwner()) {
            return $document->tenant_id === $user->tenant_id;
        }

        return false;
    }

    public function review(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
