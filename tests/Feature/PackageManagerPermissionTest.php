<?php

namespace Tests\Feature;

use App\Livewire\PackageManager;
use App\Models\Hall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PackageManagerPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_manager_can_view_packages_but_cannot_mutate_them(): void
    {
        $hall = Hall::query()->firstOrFail();
        $manager = User::factory()->create([
            'tenant_id' => $hall->tenant_id,
            'role' => 'ROLE_MANAGER',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($manager)
            ->test(PackageManager::class)
            ->assertOk()
            ->call('openCreateModal')
            ->assertForbidden();

        Livewire::actingAs($manager)
            ->test(PackageManager::class)
            ->set('menuItems', ['Chicken Biryani'])
            ->call('removeMenuItem', 0)
            ->assertForbidden();
    }

    public function test_package_price_cannot_overflow_decimal_storage(): void
    {
        $hall = Hall::query()->firstOrFail();
        $owner = User::query()
            ->where('tenant_id', $hall->tenant_id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();

        Livewire::actingAs($owner)
            ->test(PackageManager::class)
            ->set('packageName', 'Overflow Catering Package')
            ->set('perHeadPrice', 100_000_000)
            ->set('minGuests', 50)
            ->set('menuItems', ['Chicken Biryani'])
            ->call('savePackage')
            ->assertHasErrors(['perHeadPrice']);

        $this->assertDatabaseMissing('packages', [
            'tenant_id' => $owner->tenant_id,
            'name' => 'Overflow Catering Package',
        ]);
    }
}
