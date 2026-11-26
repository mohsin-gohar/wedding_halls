<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use App\Support\PermissionMatrix;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isCustomer()
            || PermissionMatrix::userCan($user, 'bookings.view');
    }

    public function view(User $user, Booking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCustomer()) {
            return (string) $booking->customer_id === (string) $user->id;
        }

        return $user->tenant_id
            && (string) $booking->tenant_id === (string) $user->tenant_id
            && PermissionMatrix::userCan($user, 'bookings.view');
    }

    public function create(User $user): bool
    {
        return $user->isCustomer()
            || $user->isSuperAdmin()
            || PermissionMatrix::userCan($user, 'bookings.create');
    }

    public function update(User $user, Booking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return ! $user->isCustomer()
            && $user->tenant_id
            && (string) $booking->tenant_id === (string) $user->tenant_id
            && PermissionMatrix::userCan($user, 'bookings.update');
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCustomer()) {
            return false;
        }

        return $user->tenant_id
            && (string) $booking->tenant_id === (string) $user->tenant_id
            && PermissionMatrix::userCan($user, 'bookings.cancel');
    }

    public function manage(User $user): bool
    {
        return $user->isSuperAdmin() || PermissionMatrix::userCan($user, 'bookings.manage');
    }
}
