<?php

namespace Tests\Feature;

use App\Models\Hall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiServiceBookingPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_inventory_staff_without_service_booking_permission_cannot_read_vendor_bookings(): void
    {
        $hall = Hall::query()->firstOrFail();
        $inventoryManager = User::factory()->create([
            'tenant_id' => $hall->tenant_id,
            'role' => 'ROLE_INVENTORY_MANAGER',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($inventoryManager)
            ->getJson('/api/v1/service-bookings')
            ->assertForbidden();

        $this->actingAs($inventoryManager)
            ->getJson('/api/v1/service-bookings/00000000-0000-4000-8000-000000000001')
            ->assertForbidden();
    }

    public function test_tenant_owner_with_service_booking_permission_can_list_vendor_bookings(): void
    {
        $hall = Hall::query()->firstOrFail();
        $owner = User::query()
            ->where('tenant_id', $hall->tenant_id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();

        $this->actingAs($owner)
            ->getJson('/api/v1/service-bookings')
            ->assertOk();
    }
}
