<?php

namespace Tests\Feature;

use App\Livewire\ExpenseTracker;
use App\Models\Expense;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseVoidIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_voiding_expense_retains_audit_record_and_excludes_it_from_financial_totals(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $accountant = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_ACCOUNTANT',
            'status' => 'ACTIVE',
        ]);

        $voided = Expense::create([
            'tenant_id' => $tenant->id,
            'category' => 'OTHER',
            'title' => 'Duplicate supplier receipt',
            'amount' => 1500,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'CASH',
            'created_by_user_id' => $accountant->id,
        ]);
        Expense::create([
            'tenant_id' => $tenant->id,
            'category' => 'OTHER',
            'title' => 'Valid cleaning supplies',
            'amount' => 2000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'CASH',
            'created_by_user_id' => $accountant->id,
        ]);

        Livewire::actingAs($accountant)
            ->test(ExpenseTracker::class)
            ->call('openVoidModal', $voided->id)
            ->set('voidReason', 'Duplicate receipt entered by mistake')
            ->call('voidExpense')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'id' => $voided->id,
            'is_voided' => true,
            'voided_by_user_id' => $accountant->id,
            'void_reason' => 'Duplicate receipt entered by mistake',
        ]);

        $this->assertNull(Expense::query()->find($voided->id));
        $this->assertNotNull(Expense::withVoided()->find($voided->id));
        $this->assertSame(
            2000.0,
            (float) Expense::query()->where('tenant_id', $tenant->id)->sum('amount')
        );
    }

    public function test_void_reason_is_required_and_bounded(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $accountant = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_ACCOUNTANT',
            'status' => 'ACTIVE',
        ]);
        $expense = Expense::create([
            'tenant_id' => $tenant->id,
            'category' => 'OTHER',
            'title' => 'Expense needing review',
            'amount' => 500,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'CASH',
            'created_by_user_id' => $accountant->id,
        ]);

        Livewire::actingAs($accountant)
            ->test(ExpenseTracker::class)
            ->call('openVoidModal', $expense->id)
            ->set('voidReason', 'short')
            ->call('voidExpense')
            ->assertHasErrors(['voidReason']);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'is_voided' => false,
        ]);
    }
    public function test_utility_linked_expense_must_be_corrected_through_utility_workflow(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $accountant = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_ACCOUNTANT',
            'status' => 'ACTIVE',
        ]);
        $expense = Expense::create([
            'tenant_id' => $tenant->id,
            'category' => 'GENERATOR_DIESEL',
            'title' => 'Linked generator fuel expense',
            'amount' => 1000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'CASH',
            'created_by_user_id' => $accountant->id,
        ]);
        UtilityLog::create([
            'tenant_id' => $tenant->id,
            'expense_id' => $expense->id,
            'generator_run_hours' => 1,
            'diesel_liters_consumed' => 10,
            'diesel_rate_per_liter' => 100,
            'wapda_units_consumed' => 0,
            'wapda_unit_rate' => 0,
            'total_utility_cost' => 1000,
            'logged_by' => $accountant->name,
            'notes' => 'Source-linked utility record',
        ]);

        Livewire::actingAs($accountant)
            ->test(ExpenseTracker::class)
            ->call('openVoidModal', $expense->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'is_voided' => false,
        ]);
    }

}
