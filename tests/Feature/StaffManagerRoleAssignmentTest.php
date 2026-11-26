<?php

namespace Tests\Feature;

use App\Livewire\StaffManager;
use App\Models\Hall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class StaffManagerRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_hr_manager_can_only_assign_the_lowest_privilege_staff_role(): void
    {
        $hall = Hall::query()->firstOrFail();
        $hrManager = User::factory()->create([
            'tenant_id' => $hall->tenant_id,
            'role' => 'ROLE_HR_MANAGER',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($hrManager);
        $method = new ReflectionMethod(StaffManager::class, 'allowedRoles');
        $method->setAccessible(true);

        self::assertSame(['ROLE_STAFF_MEMBER'], $method->invoke(new StaffManager));
    }

    public function test_tenant_owner_retains_controlled_role_assignment(): void
    {
        $hall = Hall::query()->firstOrFail();
        $owner = User::query()
            ->where('tenant_id', $hall->tenant_id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();

        $this->actingAs($owner);
        $method = new ReflectionMethod(StaffManager::class, 'allowedRoles');
        $method->setAccessible(true);
        $roles = $method->invoke(new StaffManager);

        self::assertContains('ROLE_ACCOUNTANT', $roles);
        self::assertContains('ROLE_HR_MANAGER', $roles);
        self::assertContains('ROLE_BOOKING_MANAGER', $roles);
        self::assertNotContains('ROLE_SUPER_ADMIN', $roles);
    }
}
