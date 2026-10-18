<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Livewire\Dashboard;
use App\Models\Expense;
use App\Models\Hall;
use App\Models\PlatformTask;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardsRoleAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_super_admin_can_manage_platform_tasks_and_traffic_switches(): void
    {
        $admin = User::where('role', 'ROLE_SUPER_ADMIN')->first();
        $this->assertNotNull($admin);

        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->set('newTaskTitle', 'Audit Margalla Marquee wedding hall pricing')
            ->set('newTaskCategory', 'VENDOR_ONBOARDING')
            ->set('newTaskPriority', 'HIGH')
            ->set('newTaskDueDate', now()->addDays(3)->format('Y-m-d'))
            ->call('addTask')
            ->assertHasNoErrors();

        $task = PlatformTask::where('title', 'Audit Margalla Marquee wedding hall pricing')->first();
        $this->assertNotNull($task);
        $this->assertEquals('HIGH', $task->priority);
        $this->assertFalse($task->is_completed);

        // Toggle task
        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->call('toggleTask', $task->id);

        $this->assertTrue($task->fresh()->is_completed);

        // Toggle registration & marketplace switches
        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->call('toggleRegistrationStatus')
            ->call('toggleMarketplaceStatus');

        // Delete task
        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->call('deleteTask', $task->id);

        $this->assertNull(PlatformTask::find($task->id));
    }

    public function test_hall_admin_can_log_daily_operational_kharcha_and_generator_fuel(): void
    {
        $owner = User::where('role', 'ROLE_TENANT_OWNER')->first();
        $this->assertNotNull($owner);
        $hall = Hall::where('tenant_id', $owner->tenant_id)->first();
        $this->assertNotNull($hall);

        // Test logging generator diesel fuel kharcha
        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->call('openDailyExpenseModal', 'GENERATOR_DIESEL')
            ->set('fuelDieselLiters', 60.0)
            ->set('fuelRatePerLiter', 290.0)
            ->set('fuelRunHours', 5.0)
            ->set('expenseHallId', $hall->id)
            ->set('expensePayee', 'PSO Station Mall Road')
            ->set('expensePaymentMethod', 'CASH')
            ->set('expenseReceiptNumber', 'PSO-7788')
            ->call('saveDailyExpense')
            ->assertHasNoErrors();

        // Verify expense record
        $expense = Expense::where('tenant_id', $owner->tenant_id)
            ->where('receipt_number', 'PSO-7788')
            ->first();

        $this->assertNotNull($expense);
        $this->assertEquals(17400.0, (float) $expense->amount); // 60 * 290
        $this->assertEquals('PSO Station Mall Road', $expense->payee);

        // Verify synced utility log record
        $utilityLog = UtilityLog::where('tenant_id', $owner->tenant_id)
            ->latest()
            ->first();

        $this->assertNotNull($utilityLog);
        $this->assertEquals(60.0, (float) $utilityLog->diesel_liters_consumed);
        $this->assertEquals(290.0, (float) $utilityLog->diesel_rate_per_liter);
        $this->assertEquals(5.0, (float) $utilityLog->generator_run_hours);

        // Delete the expense
        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->call('deleteDailyExpense', $expense->id);

        $this->assertNull(Expense::find($expense->id));
    }
}
