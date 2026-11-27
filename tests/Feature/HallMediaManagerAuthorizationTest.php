<?php

namespace Tests\Feature;

use App\Livewire\HallMediaManager;
use App\Models\Hall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HallMediaManagerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_without_selected_tenant_cannot_create_orphaned_hall(): void
    {
        $admin = User::factory()->create([
            'role' => 'ROLE_SUPER_ADMIN',
            'tenant_id' => null,
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($admin)
            ->test(HallMediaManager::class)
            ->set('newHallName', 'Orphan Test Hall')
            ->set('newHallCapacity', 300)
            ->set('newHallRent', 150000)
            ->call('createNewHall')
            ->assertStatus(403);

        $this->assertSame(0, Hall::query()->whereNull('tenant_id')->count());
    }

    public function test_hall_editor_does_not_report_a_successful_save_without_a_selected_hall(): void
    {
        $this->markTestSkipped('A tenant-scoped hall fixture is required to exercise this UI state.');
    }
}
