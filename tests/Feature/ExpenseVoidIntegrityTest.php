<?php

namespace Tests\Feature;

use App\Livewire\ExpenseTracker;
use App\Models\Expense;
use App\Models\Tenant;
use App\Models\User;
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
}
