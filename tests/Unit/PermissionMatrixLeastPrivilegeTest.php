<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\PermissionMatrix;
use PHPUnit\Framework\TestCase;

class PermissionMatrixLeastPrivilegeTest extends TestCase
{
    public function test_tenant_owner_wildcard_permissions_remain_effective(): void
    {
        $owner = new User(['role' => 'ROLE_TENANT_OWNER']);

        self::assertTrue(PermissionMatrix::userCan($owner, 'bookings.view'));
        self::assertTrue(PermissionMatrix::userCan($owner, 'bookings.create'));
        self::assertTrue(PermissionMatrix::userCan($owner, 'service-bookings.update'));
    }

    public function test_hr_manager_keeps_hr_permissions_without_general_manager_privileges(): void
    {
        $hr = new User(['role' => 'ROLE_HR_MANAGER']);

        self::assertTrue(PermissionMatrix::userCan($hr, 'staff.manage'));
        self::assertTrue(PermissionMatrix::userCan($hr, 'payroll.manage'));
        self::assertFalse(PermissionMatrix::userCan($hr, 'bookings.update'));
        self::assertFalse(PermissionMatrix::userCan($hr, 'expenses.manage'));
        self::assertFalse(PermissionMatrix::userCan($hr, 'inventory.manage'));
    }

    public function test_booking_manager_uses_its_own_workflow_permissions(): void
    {
        $manager = new User(['role' => 'ROLE_BOOKING_MANAGER']);

        self::assertTrue(PermissionMatrix::userCan($manager, 'bookings.cancel'));
        self::assertTrue(PermissionMatrix::userCan($manager, 'service-bookings.update'));
        self::assertTrue(PermissionMatrix::userCan($manager, 'rsvps.manage'));
        self::assertFalse(PermissionMatrix::userCan($manager, 'service-bookings.create'));
        self::assertFalse(PermissionMatrix::userCan($manager, 'expenses.manage'));
    }

    public function test_catering_staff_cannot_inherit_catering_package_or_inventory_management(): void
    {
        $catering = new User(['role' => 'ROLE_CATERING_STAFF']);

        self::assertTrue(PermissionMatrix::userCan($catering, 'kitchen.manage'));
        self::assertTrue(PermissionMatrix::userCan($catering, 'inventory.view'));
        self::assertFalse(PermissionMatrix::userCan($catering, 'packages.manage'));
        self::assertFalse(PermissionMatrix::userCan($catering, 'inventory.manage'));
    }

    public function test_distinct_enterprise_roles_do_not_inherit_legacy_manager_or_front_desk_helpers(): void
    {
        $hr = new User(['role' => 'ROLE_HR_MANAGER']);
        $bookingManager = new User(['role' => 'ROLE_BOOKING_MANAGER']);

        self::assertFalse($hr->isManager());
        self::assertTrue($hr->isHrManager());
        self::assertTrue($bookingManager->isBookingManager());
        self::assertFalse($bookingManager->isFrontDesk());
    }

    public function test_manager_operational_permissions_do_not_include_business_or_finance_mutations(): void
    {
        $manager = new User(['role' => 'ROLE_MANAGER']);

        self::assertTrue(PermissionMatrix::userCan($manager, 'bookings.create'));
        self::assertTrue(PermissionMatrix::userCan($manager, 'floor-plans.manage'));
        self::assertTrue(PermissionMatrix::userCan($manager, 'utilities.manage'));
        self::assertTrue(PermissionMatrix::userCan($manager, 'rsvps.manage'));
        self::assertFalse(PermissionMatrix::userCan($manager, 'business.update'));
        self::assertFalse(PermissionMatrix::userCan($manager, 'services.manage'));
        self::assertFalse(PermissionMatrix::userCan($manager, 'staff.manage'));
        self::assertFalse(PermissionMatrix::userCan($manager, 'expenses.manage'));
    }
}
