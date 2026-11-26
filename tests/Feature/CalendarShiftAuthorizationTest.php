<?php

namespace Tests\Feature;

use App\Livewire\CalendarShiftGrid;
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
}
