<?php

namespace Tests\Feature;

use App\Livewire\CalendarShiftGrid;
use App\Livewire\WalkInBookingModal;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarShiftAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_general_manager_can_view_calendar_but_cannot_change_shift_policy(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_MANAGER',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($manager)
            ->test(CalendarShiftGrid::class)
            ->assertOk()
            ->call('saveShiftPolicy')
            ->assertStatus(403);
    }

    public function test_tenant_owner_can_change_shift_policy(): void
    {
        $owner = User::query()
            ->whereNotNull('tenant_id')
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();

        Livewire::actingAs($owner)
            ->test(CalendarShiftGrid::class)
            ->call('saveShiftPolicy')
            ->assertHasNoErrors();
    }

    public function test_walk_in_terminal_requires_booking_create_or_manage_permission(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $chef = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_CHEF',
            'status' => 'ACTIVE',
        ]);
        $floorStaff = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_FLOOR_STAFF',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($chef)
            ->test(CalendarShiftGrid::class)
            ->call('openWalkInModal')
            ->assertStatus(403);

        // Floor staff are operational viewers only: creating CONFIRMED bookings
        // and collecting cash advances is reserved for front desk/managers.
        Livewire::actingAs($floorStaff)
            ->test(WalkInBookingModal::class)
            ->assertSet('isStaff', false);
    }

    public function test_service_staff_without_booking_visibility_cannot_open_tenant_calendar(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $serviceStaff = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_SERVICE_STAFF',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($serviceStaff)
            ->test(CalendarShiftGrid::class)
            ->assertStatus(403);
    }
}
