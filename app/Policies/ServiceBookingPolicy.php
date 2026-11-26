<?php

namespace App\Policies;

use App\Models\ServiceBooking;
use App\Models\User;
use App\Support\PermissionMatrix;

class ServiceBookingPolicy
{
    public function view(User $user, ServiceBooking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCustomer()) {
            return (string) $booking->customer_id === (string) $user->id;
        }

        return $user->tenant_id
            && (string) $booking->tenant_id === (string) $user->tenant_id
            && PermissionMatrix::userCan($user, 'service-bookings.view');
    }

    public function create(User $user): bool
    {
        return $user->isCustomer()
            || $user->isSuperAdmin()
            || PermissionMatrix::userCan($user, 'service-bookings.create');
    }

    public function update(User $user, ServiceBooking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return ! $user->isCustomer()
            && $user->tenant_id
            && (string) $booking->tenant_id === (string) $user->tenant_id
            && PermissionMatrix::userCan($user, 'service-bookings.update');
    }

    public function cancel(User $user, ServiceBooking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCustomer()) {
            return (string) $booking->customer_id === (string) $user->id;
        }

        return $user->tenant_id
            && (string) $booking->tenant_id === (string) $user->tenant_id
            && PermissionMatrix::userCan($user, 'service-bookings.cancel');
    }

    public function confirm(User $user, ServiceBooking $booking): bool
    {
        return $user->isSuperAdmin()
            || ($user->tenant_id
                && (string) $booking->tenant_id === (string) $user->tenant_id
                && PermissionMatrix::userCan($user, 'service-bookings.confirm'));
    }

    public function complete(User $user, ServiceBooking $booking): bool
    {
        return $user->isSuperAdmin()
            || ($user->tenant_id
                && (string) $booking->tenant_id === (string) $user->tenant_id
                && PermissionMatrix::userCan($user, 'service-bookings.complete'));
    }
}
