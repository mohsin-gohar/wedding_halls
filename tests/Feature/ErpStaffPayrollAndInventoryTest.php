<?php

namespace Tests\Feature;

use App\Livewire\InventoryTracker;
use App\Livewire\StaffAttendance;
use App\Livewire\StaffPayroll;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\StaffPayroll as PayrollModel;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ErpStaffPayrollAndInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_staff_attendance_and_leave_workflow(): void
    {
        $tenant = Tenant::create([
            'name' => 'Pearl Continental Banquets',
            'slug' => 'pearl-continental',
            'subscription_status' => 'ACTIVE',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'General Manager Asad',
            'email' => 'gm@pcbanquets.pk',
            'password' => bcrypt('password'),
            'role' => 'ROLE_MANAGER',
        ]);

        $chef = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Ustad Bawarchi Iqbal',
            'email' => 'chef@pcbanquets.pk',
            'password' => bcrypt('password'),
            'role' => 'ROLE_CHEF',
        ]);

        $this->actingAs($manager);

        $today = Carbon::today()->format('Y-m-d');

        // Test logging daily attendance with overtime
        Livewire::test(StaffAttendance::class)
            ->set('selectedDate', $today)
            ->set("attendanceData.{$chef->id}.status", 'PRESENT')
            ->set("attendanceData.{$chef->id}.overtime_hours", 3.5)
            ->call('saveDailyAttendance');

        $this->assertDatabaseHas('staff_attendances', [
            'tenant_id' => $tenant->id,
            'user_id' => $chef->id,
            'status' => 'PRESENT',
            'overtime_hours' => 3.5,
        ]);

        // Test submitting and approving leave
        Livewire::test(StaffAttendance::class)
            ->set('leaveUserId', $chef->id)
            ->set('leaveType', 'SICK')
            ->set('leaveStartDate', Carbon::today()->addDays(2)->format('Y-m-d'))
            ->set('leaveEndDate', Carbon::today()->addDays(3)->format('Y-m-d'))
            ->set('leaveReason', 'Medical checkup and rest')
            ->call('submitLeave');

        $this->assertDatabaseHas('staff_leaves', [
            'tenant_id' => $tenant->id,
            'user_id' => $chef->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_payroll_generation_and_auto_expense_posting(): void
    {
        $tenant = Tenant::create([
            'name' => 'Shangrila Marquees',
            'slug' => 'shangrila-marquees',
            'subscription_status' => 'ACTIVE',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner Mian Waqas',
            'email' => 'waqas@shangrila.pk',
            'password' => bcrypt('password'),
            'role' => 'ROLE_TENANT_OWNER',
        ]);

        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Kashif Floor Captain',
            'email' => 'kashif@shangrila.pk',
            'password' => bcrypt('password'),
            'role' => 'ROLE_FRONT_DESK',
        ]);

        $this->actingAs($owner);

        $currentMonth = Carbon::today()->format('Y-m');

        // Compile payroll
        Livewire::test(StaffPayroll::class)
            ->set('selectedMonth', $currentMonth)
            ->call('generateMonthlyPayroll');

        $payrollRecord = PayrollModel::where('tenant_id', $tenant->id)
            ->where('user_id', $staff->id)
            ->where('month_year', $currentMonth)
            ->first();

        $this->assertNotNull($payrollRecord);
        $this->assertEquals('DRAFT', $payrollRecord->status);

        // Disburse and mark as PAID
        Livewire::test(StaffPayroll::class)
            ->set('payingPayrollId', $payrollRecord->id)
            ->set('payMethod', 'BANK_TRANSFER')
            ->set('payDate', Carbon::today()->format('Y-m-d'))
            ->call('markAsPaid');

        $payrollRecord->refresh();
        $this->assertEquals('PAID', $payrollRecord->status);

        // Verify auto-logged Expense
        $this->assertDatabaseHas('expenses', [
            'tenant_id' => $tenant->id,
            'category' => 'STAFF_WAGES',
            'payee' => $staff->name,
            'amount' => $payrollRecord->net_salary,
        ]);

        // Verify Salary Slip document route renders authorized
        $response = $this->get(route('documents.salary-slip', $payrollRecord->id));
        $response->assertStatus(200);
        $response->assertSee('Confidential Salary Slip');
        $response->assertSee($staff->name);
    }

    public function test_inventory_stock_tracking_and_purchase_orders(): void
    {
        $tenant = Tenant::create([
            'name' => 'Gulberg Heights Banquets',
            'slug' => 'gulberg-heights',
            'subscription_status' => 'ACTIVE',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Haji Mushtaq',
            'email' => 'mushtaq@gulberg.pk',
            'password' => bcrypt('password'),
            'role' => 'ROLE_TENANT_OWNER',
        ]);

        $this->actingAs($owner);

        // Create Item
        Livewire::test(InventoryTracker::class)
            ->set('itemName', 'Mutton Karahi Cut')
            ->set('itemCategory', 'RAW_FOOD')
            ->set('itemUnit', 'KG')
            ->set('itemCurrentStock', 100.0)
            ->set('itemMinThreshold', 25.0)
            ->set('itemUnitCost', 2400.0)
            ->call('saveItem');

        $item = InventoryItem::where('tenant_id', $tenant->id)->where('name', 'Mutton Karahi Cut')->first();
        $this->assertNotNull($item);
        $this->assertEquals(100.0, (float) $item->current_stock);

        // Record stock out (event usage)
        Livewire::test(InventoryTracker::class)
            ->set('movementItemId', $item->id)
            ->set('movementType', 'STOCK_OUT')
            ->set('movementQuantity', 40.0)
            ->set('movementNote', 'Walima banquet dinner usage')
            ->call('recordStockMovement');

        $item->refresh();
        $this->assertEquals(60.0, (float) $item->current_stock);
        $this->assertDatabaseHas('inventory_logs', [
            'tenant_id' => $tenant->id,
            'inventory_item_id' => $item->id,
            'type' => 'STOCK_OUT',
            'quantity' => 40.0,
        ]);

        // Create Purchase Order and mark as PAID
        Livewire::test(InventoryTracker::class)
            ->set('poSupplierName', 'Al-Noor Poultry Traders')
            ->set('poTotalAmount', 85000.0)
            ->set('poDate', Carbon::today()->format('Y-m-d'))
            ->set('poNotes', '130kg Chicken Broiler for Saturday event')
            ->call('createPurchaseOrder');

        $this->assertDatabaseHas('purchase_orders', [
            'tenant_id' => $tenant->id,
            'supplier_name' => 'Al-Noor Poultry Traders',
            'total_amount' => 85000.0,
            'status' => 'ORDERED',
        ]);

        $po = \App\Models\PurchaseOrder::where('tenant_id', $tenant->id)->first();
        Livewire::test(InventoryTracker::class)
            ->call('markPoPaid', $po->id);

        $po->refresh();
        $this->assertEquals('PAID', $po->status);

        // Verify expense posted automatically
        $this->assertDatabaseHas('expenses', [
            'tenant_id' => $tenant->id,
            'category' => 'RAW_FOOD_MEAT',
            'payee' => 'Al-Noor Poultry Traders',
            'amount' => 85000.0,
        ]);
    }
}
