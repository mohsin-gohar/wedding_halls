<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\InventoryTracker;
use App\Livewire\StaffAttendance;
use App\Livewire\StaffManager;
use App\Livewire\StaffPayroll;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\StaffAttendance as AttendanceModel;
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
            ->set("attendanceData.{$chef->id}.check_in", '09:30')
            ->set("attendanceData.{$chef->id}.check_out", '22:00')
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

    public function test_dashboard_daily_operations_ledger_can_browse_months_and_shows_full_month_totals(): void
    {
        $tenant = Tenant::create([
            'name' => 'Monthly Ledger Banquets',
            'slug' => 'monthly-ledger-banquets',
            'subscription_status' => 'ACTIVE',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);
        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Ledger Owner',
            'email' => 'ledger-owner@example.test',
            'password' => bcrypt('password'),
            'role' => 'ROLE_TENANT_OWNER',
        ]);

        $month = Carbon::today()->format('Y-m');
        $monthStart = Carbon::today()->startOfMonth();
        $makeExpense = function (string $date, string $title, string $category, float $amount) use ($tenant, $owner): void {
            Expense::create([
                'tenant_id' => $tenant->id,
                'category' => $category,
                'title' => $title,
                'amount' => $amount,
                'expense_date' => $date,
                'payee' => 'Local Supplier',
                'payment_method' => 'CASH',
                'created_by_user_id' => $owner->id,
            ]);
        };

        $makeExpense($monthStart->copy()->addDays(2)->format('Y-m-d'), 'Monthly generator diesel', 'GENERATOR_DIESEL', 12000);
        $makeExpense($monthStart->copy()->addDays(3)->format('Y-m-d'), 'Monthly staff wages', 'STAFF_DAILY_WAGES', 8000);
        $makeExpense($monthStart->copy()->subMonth()->startOfMonth()->format('Y-m-d'), 'Previous month only', 'GENERATOR_DIESEL', 50000);

        $ledger = Livewire::actingAs($owner)->test(Dashboard::class)
            ->set('operationsMonth', $month)
            ->assertViewHas('operationsExpensesTotal', 20000.0)
            ->assertViewHas('operationsDieselCost', 12000.0)
            ->assertViewHas('operationsExpenseCount', 2)
            ->assertViewHas('recentExpenses', fn ($records) => $records->count() === 2
                && $records->contains('title', 'Monthly generator diesel')
                && ! $records->contains('title', 'Previous month only'));

        $ledger->call('previousOperationsMonth')
            ->assertSet('operationsMonth', Carbon::today()->subMonth()->format('Y-m'))
            ->assertViewHas('operationsExpensesTotal', 50000.0)
            ->assertViewHas('recentExpenses', fn ($records) => $records->contains('title', 'Previous month only'));
    }

    public function test_staff_attendance_monthly_summary_respects_selected_month(): void
    {
        $tenant = Tenant::create([
            'name' => 'Attendance Month Banquets',
            'slug' => 'attendance-month-banquets',
            'subscription_status' => 'ACTIVE',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);
        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Manager',
            'email' => 'attendance-manager@example.test',
            'password' => bcrypt('password'),
            'role' => 'ROLE_MANAGER',
        ]);
        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Monthly Staff Member',
            'email' => 'monthly-staff@example.test',
            'password' => bcrypt('password'),
            'role' => 'ROLE_CHEF',
        ]);

        $currentMonth = Carbon::today()->startOfMonth();
        AttendanceModel::create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'date' => $currentMonth->copy()->addDays(2)->format('Y-m-d'),
            'status' => 'PRESENT',
            'overtime_hours' => 2.5,
        ]);
        AttendanceModel::create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'date' => $currentMonth->copy()->subMonth()->startOfMonth()->format('Y-m-d'),
            'status' => 'ABSENT',
            'overtime_hours' => 0,
        ]);

        Livewire::actingAs($manager)->test(StaffAttendance::class)
            ->call('setTab', 'monthly')
            ->set('selectedMonth', $currentMonth->format('Y-m'))
            ->assertViewHas('summary', function ($summary) use ($staff) {
                return $summary[$staff->id]['present'] === 1
                    && $summary[$staff->id]['absent'] === 0
                    && (float) $summary[$staff->id]['overtime_hours'] === 2.5;
            })
            ->call('previousMonth')
            ->assertSet('selectedMonth', Carbon::today()->subMonth()->format('Y-m'))
            ->assertViewHas('summary', fn ($summary) => $summary[$staff->id]['absent'] === 1
                && $summary[$staff->id]['present'] === 0);
    }

    public function test_staff_profiles_filters_salary_and_archiving_preserve_payroll_history(): void
    {
        $tenant = Tenant::create([
            'name' => 'Staff Lifecycle Banquets',
            'slug' => 'staff-lifecycle-banquets',
            'subscription_status' => 'ACTIVE',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);
        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lifecycle Owner',
            'email' => 'lifecycle-owner@example.test',
            'password' => bcrypt('password'),
            'role' => 'ROLE_TENANT_OWNER',
        ]);
        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lifecycle Chef',
            'email' => 'lifecycle-chef@example.test',
            'password' => bcrypt('password'),
            'role' => 'ROLE_CHEF',
            'base_salary' => 55000,
        ]);
        $otherStaff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Other Receptionist',
            'email' => 'lifecycle-reception@example.test',
            'password' => bcrypt('password'),
            'role' => 'ROLE_FRONT_DESK',
        ]);

        $date = Carbon::today()->format('Y-m-d');
        AttendanceModel::create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'date' => $date,
            'status' => 'PRESENT',
            'overtime_hours' => 1,
        ]);
        AttendanceModel::create([
            'tenant_id' => $tenant->id,
            'user_id' => $otherStaff->id,
            'date' => $date,
            'status' => 'ABSENT',
            'overtime_hours' => 0,
        ]);

        $month = Carbon::today()->format('Y-m');
        $payroll = PayrollModel::create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'month_year' => $month,
            'basic_salary' => 55000,
            'overtime_hours' => 1,
            'overtime_rate' => 250,
            'allowances' => 0,
            'deductions' => 0,
            'net_salary' => 55250,
            'status' => 'DRAFT',
            'payment_method' => 'CASH',
        ]);

        Livewire::actingAs($owner)->test(StaffAttendance::class)
            ->set('selectedStaffId', (string) $staff->id)
            ->call('setTab', 'monthly')
            ->assertViewHas('summary', fn ($summary) => array_keys($summary) === [$staff->id]);

        Livewire::actingAs($owner)->test(StaffPayroll::class)
            ->set('selectedMonth', $month)
            ->set('selectedStaffId', (string) $staff->id)
            ->assertViewHas('payrolls', fn ($payrolls) => $payrolls->count() === 1 && $payrolls->first()->id === $payroll->id)
            ->call('generateMonthlyPayroll');
        $this->assertDatabaseHas('staff_payrolls', [
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'basic_salary' => 55000,
        ]);

        Livewire::actingAs($owner)->test(StaffManager::class)
            ->call('openEditModal', $staff->id)
            ->set('editBaseSalary', '62000')
            ->call('updateStaffMember')
            ->assertHasNoErrors();
        $this->assertSame(62000.0, (float) $staff->fresh()->base_salary);

        Livewire::actingAs($owner)->test(StaffManager::class)
            ->call('removeStaffMember', $staff->id)
            ->assertViewHas('staffMembers', fn ($members) => ! $members->contains('id', $staff->id));

        $this->assertSoftDeleted('users', ['id' => $staff->id]);
        $this->assertDatabaseHas('staff_payrolls', ['id' => $payroll->id, 'user_id' => $staff->id]);
        $this->assertDatabaseHas('staff_attendances', ['tenant_id' => $tenant->id, 'user_id' => $staff->id]);

        Livewire::actingAs($owner)->test(StaffManager::class)
            ->set('showFormerStaff', true)
            ->assertViewHas('staffMembers', fn ($members) => $members->contains('id', $staff->id))
            ->call('restoreStaffMember', $staff->id);
        $this->assertNotSoftDeleted('users', ['id' => $staff->id]);
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
            'base_salary' => 38000,
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

        Livewire::test(StaffPayroll::class)
            ->set('payingPayrollId', $payrollRecord->id)
            ->set('payDate', Carbon::today()->format('Y-m-d'))
            ->set('payMethod', 'CASH')
            ->call('markAsPaid');

        $payrollRecord->refresh();
        $this->assertSame('DRAFT', $payrollRecord->status);
        $this->assertDatabaseMissing('expenses', [
            'payroll_id' => $payrollRecord->id,
        ]);

        Livewire::test(StaffPayroll::class)
            ->call('markAsApproved', $payrollRecord->id);

        Livewire::test(StaffPayroll::class)
            ->set('payingPayrollId', $payrollRecord->id)
            ->set('payMethod', 'CASH')
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

        $po = PurchaseOrder::where('tenant_id', $tenant->id)->first();
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

    public function test_staff_manager_profile_toggle_status_and_purge(): void
    {
        $tenant = Tenant::create([
            'name' => 'Royal Horizon Banquets',
            'slug' => 'royal-horizon-banquets',
            'subscription_status' => 'ACTIVE',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Malik Salman',
            'email' => 'salman@horizon.pk',
            'password' => bcrypt('password'),
            'role' => 'ROLE_TENANT_OWNER',
        ]);

        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Bilal Waiter',
            'email' => 'bilal@horizon.pk',
            'password' => bcrypt('password'),
            'role' => 'ROLE_FLOOR_STAFF',
            'base_salary' => 30000,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($owner);

        // 1. Open Profile Modal & verify stats
        Livewire::test(StaffManager::class)
            ->call('openProfileModal', $staff->id)
            ->assertSet('showProfileModal', true)
            ->assertSet('profileStaffId', $staff->id)
            ->assertSee('Bilal Waiter')
            ->assertSee('PKR 30,000');

        // 2. Toggle Status (Active -> Suspended -> Active)
        Livewire::test(StaffManager::class)
            ->call('toggleStaffStatus', $staff->id);

        $staff->refresh();
        $this->assertEquals('SUSPENDED', $staff->status);

        Livewire::test(StaffManager::class)
            ->call('toggleStaffStatus', $staff->id);

        $staff->refresh();
        $this->assertEquals('ACTIVE', $staff->status);

        // 3. Archive the staff account while retaining financial history.
        $payroll = PayrollModel::create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'month_year' => Carbon::today()->format('Y-m'),
            'basic_salary' => 30000,
            'net_salary' => 30000,
            'status' => 'PAID',
            'payment_method' => 'CASH',
        ]);

        Livewire::test(StaffManager::class)
            ->call('removeStaffMember', $staff->id);

        $this->assertSoftDeleted('users', [
            'id' => $staff->id,
        ]);
        $this->assertDatabaseHas('staff_payrolls', [
            'id' => $payroll->id,
            'user_id' => $staff->id,
            'status' => 'PAID',
        ]);
        $this->assertSame(
            (string) $staff->id,
            (string) PayrollModel::with('user')->findOrFail($payroll->id)->user->id
        );
    }

    public function test_one_click_attendance_filtering_and_quick_edit_modal(): void
    {
        $tenant = Tenant::create([
            'name' => 'Imperial Heights Palace',
            'slug' => 'imperial-heights-palace',
            'subscription_status' => 'ACTIVE',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sardar Aurangzeb',
            'email' => 'aurangzeb@imperial.pk',
            'password' => bcrypt('password'),
            'role' => 'ROLE_TENANT_OWNER',
        ]);

        $staff1 = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Ustad Bawarchi Tariq',
            'email' => 'tariq@imperial.pk',
            'password' => bcrypt('password'),
            'role' => 'ROLE_CHEF',
        ]);

        $staff2 = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Farhan Receptionist',
            'email' => 'farhan@imperial.pk',
            'password' => bcrypt('password'),
            'role' => 'ROLE_FRONT_DESK',
        ]);

        $this->actingAs($owner);
        $today = Carbon::today()->format('Y-m-d');

        // 1. 1-click selectStaff filters to only staff1
        Livewire::test(StaffAttendance::class)
            ->call('selectStaff', (string) $staff1->id)
            ->assertSet('selectedStaffId', (string) $staff1->id)
            ->assertSee('Ustad Bawarchi Tariq');

        // 2. Quick Edit Attendance Record Modal
        Livewire::test(StaffAttendance::class)
            ->call('openEditRecordModal', $staff1->id, $today)
            ->assertSet('showEditRecordModal', true)
            ->assertSet('editingUserId', $staff1->id)
            ->set('editingStatus', 'PRESENT')
            ->set('editingCheckIn', '09:30')
            ->set('editingCheckOut', '23:00')
            ->set('editingOvertime', 4.5)
            ->set('editingNotes', 'Handled massive bridal feast')
            ->call('saveEditedRecord')
            ->assertSet('showEditRecordModal', false);

        $this->assertDatabaseHas('staff_attendances', [
            'tenant_id' => $tenant->id,
            'user_id' => $staff1->id,
            'status' => 'PRESENT',
            'overtime_hours' => 4.5,
            'check_in' => '09:30',
        ]);
    }

    public function test_one_click_payroll_filtering_and_base_salary_sync(): void
    {
        $tenant = Tenant::create([
            'name' => 'Crown Grand Banquets',
            'slug' => 'crown-grand-banquets',
            'subscription_status' => 'ACTIVE',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Chaudhry Nisar',
            'email' => 'nisar@crown.pk',
            'password' => bcrypt('password'),
            'role' => 'ROLE_TENANT_OWNER',
        ]);

        $employee = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Waqas Floor Manager',
            'email' => 'waqas@crown.pk',
            'password' => bcrypt('password'),
            'role' => 'ROLE_MANAGER',
            'base_salary' => 60000,
        ]);

        $this->actingAs($owner);
        $month = Carbon::today()->format('Y-m');

        // 1. Compile monthly payroll
        Livewire::test(StaffPayroll::class)
            ->set('selectedMonth', $month)
            ->call('generateMonthlyPayroll');

        $record = PayrollModel::where('tenant_id', $tenant->id)
            ->where('user_id', $employee->id)
            ->where('month_year', $month)
            ->first();

        $this->assertNotNull($record);

        // 2. Select staff with 1-click
        Livewire::test(StaffPayroll::class)
            ->call('selectStaff', (string) $employee->id)
            ->assertSet('selectedStaffId', (string) $employee->id)
            ->assertSee('Waqas Floor Manager');

        // 3. Edit salary and check sync to user master profile
        Livewire::test(StaffPayroll::class)
            ->call('openEditModal', $record->id)
            ->assertSet('showEditModal', true)
            ->set('editBasicSalary', 72000)
            ->set('editOvertimeHours', 10)
            ->set('editOvertimeRate', 300)
            ->set('updateBaseSalaryOnProfile', true)
            ->call('updatePayroll')
            ->assertSet('showEditModal', false);

        $record->refresh();
        $this->assertEquals(72000, (float) $record->basic_salary);

        // Verify base salary was synced to user master record!
        $employee->refresh();
        $this->assertEquals(72000, (float) $employee->base_salary);
    }
    public function test_general_manager_without_staff_manage_permission_cannot_open_staff_manager(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_MANAGER',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($manager)
            ->test(StaffManager::class)
            ->assertStatus(403);
    }

    public function test_inventory_mutations_require_inventory_manage_permission(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_MANAGER',
            'status' => 'ACTIVE',
        ]);
        $chef = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_CHEF',
            'status' => 'ACTIVE',
        ]);
        $inventoryManager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_INVENTORY_MANAGER',
            'status' => 'ACTIVE',
        ]);
        $accountant = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_ACCOUNTANT',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($manager)
            ->test(InventoryTracker::class)
            ->call('saveItem')
            ->assertStatus(403);

        Livewire::actingAs($chef)
            ->test(InventoryTracker::class)
            ->call('saveItem')
            ->assertStatus(403);

        Livewire::actingAs($inventoryManager)
            ->test(InventoryTracker::class)
            ->assertOk();

        Livewire::actingAs($accountant)
            ->test(InventoryTracker::class)
            ->assertOk()
            ->assertDontSee('+ Add Inventory Item')
            ->call('saveItem')
            ->assertStatus(403);

        Livewire::actingAs($accountant)
            ->test(InventoryTracker::class)
            ->assertOk()
            ->set('poSupplierName', 'Accountant Approved Supplier')
            ->set('poTotalAmount', 12500)
            ->set('poDate', Carbon::today()->toDateString())
            ->call('createPurchaseOrder')
            ->assertHasNoErrors();

        $purchaseOrder = PurchaseOrder::query()
            ->where('tenant_id', $tenant->id)
            ->where('supplier_name', 'Accountant Approved Supplier')
            ->firstOrFail();

        Livewire::actingAs($accountant)
            ->test(InventoryTracker::class)
            ->call('markPoPaid', $purchaseOrder->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => 'PAID',
        ]);
        $this->assertDatabaseHas('expenses', [
            'tenant_id' => $tenant->id,
            'receipt_number' => $purchaseOrder->po_number,
            'payment_method' => 'CASH',
        ]);
    }

    public function test_hr_manager_cannot_assign_or_manage_elevated_finance_roles(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $hrManager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_HR_MANAGER',
            'status' => 'ACTIVE',
        ]);
        $accountant = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_ACCOUNTANT',
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($hrManager)
            ->test(StaffManager::class)
            ->set('name', 'Elevated Finance User')
            ->set('email', 'elevated-finance@example.test')
            ->set('role', 'ROLE_ACCOUNTANT')
            ->set('password', 'secure-password-123')
            ->call('createStaffMember')
            ->assertHasErrors(['role']);

        Livewire::actingAs($hrManager)
            ->test(StaffManager::class)
            ->call('openEditModal', $accountant->id)
            ->assertStatus(403);

        $this->assertDatabaseMissing('users', [
            'email' => 'elevated-finance@example.test',
        ]);
    }

    public function test_inventory_item_removal_archives_the_record_instead_of_deleting_it(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $owner = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();

        $item = InventoryItem::create([
            'tenant_id' => $tenant->id,
            'name' => 'Archive retention regression item',
            'category' => 'OTHER',
            'unit' => 'PIECES',
            'current_stock' => 0,
            'min_threshold' => 0,
            'unit_cost' => 10,
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($owner)
            ->test(InventoryTracker::class)
            ->call('deleteItem', $item->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'tenant_id' => $tenant->id,
            'is_active' => false,
        ]);
        $this->assertNotNull(InventoryItem::find($item->id));

        $component->call('restoreItem', $item->id)->assertHasNoErrors();
        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'is_active' => true,
        ]);

        $stockedItem = InventoryItem::create([
            'tenant_id' => $tenant->id,
            'name' => 'Stocked item cannot be archived',
            'category' => 'OTHER',
            'unit' => 'PIECES',
            'current_stock' => 5,
            'min_threshold' => 1,
            'unit_cost' => 10,
            'is_active' => true,
        ]);

        Livewire::actingAs($owner)
            ->test(InventoryTracker::class)
            ->call('deleteItem', $stockedItem->id)
            ->assertStatus(422);

        $this->assertDatabaseHas('inventory_items', [
            'id' => $stockedItem->id,
            'is_active' => true,
        ]);
    }

}
