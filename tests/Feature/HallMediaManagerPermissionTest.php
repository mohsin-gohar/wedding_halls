<?php

namespace Tests\Feature;

use App\Livewire\HallMediaManager;
use App\Models\Hall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HallMediaManagerPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_manager_may_open_but_cannot_persist_hall_website_changes_without_business_update(): void
    {
        $hall = Hall::query()->firstOrFail();
        $manager = User::factory()->create([
            'tenant_id' => $hall->tenant_id,
            'role' => 'ROLE_MANAGER',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($manager)
            ->test(HallMediaManager::class)
            ->set('name', 'Unauthorized Hall Rename')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseMissing('halls', [
            'id' => $hall->id,
            'name' => 'Unauthorized Hall Rename',
        ]);
    }

    public function test_tenant_owner_can_update_hall_website_settings(): void
    {
        $hall = Hall::query()->firstOrFail();
        $owner = User::query()
            ->where('tenant_id', $hall->tenant_id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();

        Livewire::actingAs($owner)
            ->test(HallMediaManager::class)
            ->set('name', 'Owner Approved Hall Rename')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('halls', [
            'id' => $hall->id,
            'name' => 'Owner Approved Hall Rename',
        ]);
    }

    public function test_manager_cannot_write_uploads_or_service_catalogue(): void
    {
        $hall = Hall::query()->firstOrFail();
        $manager = User::factory()->create([
            'tenant_id' => $hall->tenant_id,
            'role' => 'ROLE_MANAGER',
            'status' => 'ACTIVE',
        ]);

        $originalServices = $hall->services;

        Livewire::actingAs($manager)
            ->test(HallMediaManager::class)
            ->set('newServiceTitle', 'Unauthorized Service')
            ->set('newServicePrice', 5000)
            ->set('newServiceCategory', 'Decor')
            ->call('addService')
            ->assertForbidden();

        $this->assertSame($originalServices, $hall->fresh()->services);
    }
}
