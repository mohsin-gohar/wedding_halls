<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isCustomer()) {
            return $booking->customer_id === $user->id;
        }
        if ($user->tenant_id && $booking->tenant_id === $user->tenant_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer() || $user->isFrontDesk() || $user->isManager() || $user->isTenantOwner();
    }

    public function update(User $user, Booking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isFrontDesk() || $user->isManager() || $user->isTenantOwner()) {
            return $booking->tenant_id === $user->tenant_id;
        }

        return false;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isCustomer()) {
            return $booking->customer_id === $user->id;
        }
        if ($user->isTenantOwner() || $user->isManager()) {
            return $booking->tenant_id === $user->tenant_id;
        }

        return false;
    }

    public function manage(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isTenantOwner() || $user->isManager();
    }
}
