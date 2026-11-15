<?php

namespace App\Policies;

use App\Models\ServiceBooking;
use App\Models\User;

class ServiceBookingPolicy
{
    public function view(User $user, ServiceBooking $booking): bool
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
        return $user->isCustomer() || $user->isFrontDesk() || $user->isManager();
    }

    public function update(User $user, ServiceBooking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isFrontDesk() || $user->isManager() || $user->isTenantOwner()) {
            return $booking->tenant_id === $user->tenant_id;
        }

        return false;
    }

    public function cancel(User $user, ServiceBooking $booking): bool
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

    public function confirm(User $user, ServiceBooking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isTenantOwner() || $user->isManager()) {
            return $booking->tenant_id === $user->tenant_id;
        }

        return false;
    }

    public function complete(User $user, ServiceBooking $booking): bool
    {
        return $this->confirm($user, $booking);
    }
}
