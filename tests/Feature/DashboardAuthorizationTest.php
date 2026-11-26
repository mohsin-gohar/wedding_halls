<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Booking;
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
    public function test_accountant_can_record_daily_expense_and_utility_log_atomically(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $accountant = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_ACCOUNTANT',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->set('expenseTitle', 'Dashboard expense atomicity regression')
            ->call('saveDailyExpense')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'tenant_id' => $tenant->id,
            'title' => 'Dashboard expense atomicity regression',
            'amount' => 14250,
            'payment_method' => 'CASH',
        ]);

        $this->assertDatabaseHas('utility_logs', [
            'tenant_id' => $tenant->id,
            'total_utility_cost' => 14250,
        ]);
    }

    public function test_daily_expense_cannot_link_a_booking_from_another_tenant(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $foreignBooking = Booking::query()
            ->where('tenant_id', '!=', $tenant->id)
            ->first();

        if (! $foreignBooking) {
            $this->markTestSkipped('A second tenant booking fixture is required.');
        }

        $accountant = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_ACCOUNTANT',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->set('expenseTitle', 'Dashboard expense atomicity regression')
            ->set('fuelBookingId', $foreignBooking->id)
            ->call('saveDailyExpense')
            ->assertHasErrors(['fuelBookingId']);

        $this->assertDatabaseMissing('expenses', [
            'tenant_id' => $tenant->id,
            'title' => 'Dashboard expense atomicity regression',
        ]);
    }

}
