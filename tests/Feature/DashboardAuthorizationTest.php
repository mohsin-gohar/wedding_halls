<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_general_manager_receives_operations_only_dashboard_without_financial_data(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_MANAGER',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($manager)
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('Operations Dashboard')
            ->assertDontSee('Earning / Gross')
            ->assertDontSee('Net Profit')
            ->assertDontSee('PKR');
    }

    public function test_general_manager_cannot_create_or_delete_financial_expenses_from_dashboard(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_MANAGER',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($manager)
            ->test(Dashboard::class)
            ->call('openDailyExpenseModal')
            ->assertStatus(403);
    }

    public function test_accountant_with_financial_permission_receives_full_dashboard(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $accountant = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_ACCOUNTANT',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('Earning / Gross');
    }

    public function test_service_staff_without_booking_visibility_cannot_open_hall_dashboard(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $serviceStaff = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_SERVICE_STAFF',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($serviceStaff)
            ->test(Dashboard::class)
            ->assertStatus(403);
    }
}
