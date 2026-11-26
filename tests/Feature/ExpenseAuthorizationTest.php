<?php

namespace Tests\Feature;

use App\Livewire\ExpenseTracker;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_general_manager_without_expense_permissions_cannot_open_expense_erp(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_MANAGER',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($manager)
            ->test(ExpenseTracker::class)
            ->assertStatus(403);
    }

    public function test_accountant_with_expense_permissions_can_open_expense_erp(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $accountant = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_ACCOUNTANT',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($accountant)
            ->test(ExpenseTracker::class)
            ->assertOk();
    }
}
