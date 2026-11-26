<?php

namespace Tests\Feature;

use App\Livewire\FloorPlanDesigner;
use App\Livewire\InventoryTracker;
use App\Livewire\KitchenKotBoard;
use App\Livewire\StaffAttendance;
use App\Livewire\StaffPayroll;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\Hall;
use App\Models\InventoryItem;
use App\Models\KitchenKot;
use App\Models\MenuItem;
use App\Models\PurchaseOrder;
use App\Models\StaffLeave;
use App\Models\StaffPayroll as PayrollModel;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use App\Services\KitchenOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression coverage for the verified ERP audit hardening findings.
 *
 * These tests intentionally exercise the Livewire action boundaries rather
 * than only model state, because the findings were client-trust and workflow
 * order defects.
 */
class ErpIntegrityHardeningRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_po_paid_is_idempotent_and_cash_only(): void
    {
        $tenant = $this->tenant('po-idempotency');
        $owner = $this->user($tenant, 'ROLE_TENANT_OWNER', 'owner@po-idempotency.test');

        $po = PurchaseOrder::create([
            'tenant_id' => $tenant->id,
            'po_number' => 'PO-HARDEN-0001',
            'supplier_name' => 'Al-Noor Provision Store',
            'total_amount' => 12500,
            'status' => 'ORDERED',
            'order_date' => now()->format('Y-m-d'),
        ]);

        Livewire::actingAs($owner)
            ->test(InventoryTracker::class)
            ->call('markPoPaid', $po->id)
            ->assertHasNoErrors();

        Livewire::actingAs($owner)
            ->test(InventoryTracker::class)
            ->call('markPoPaid', $po->id)
            ->assertHasNoErrors();

        $this->assertSame('PAID', $po->fresh()->status);

        $expenses = Expense::where('tenant_id', $tenant->id)
            ->where('receipt_number', $po->po_number)
            ->get();

        $this->assertCount(1, $expenses);
        $this->assertSame('CASH', $expenses->first()->payment_method);
    }

    public function test_invalid_inventory_movement_type_is_rejected(): void
    {
        $tenant = $this->tenant('movement-enum');
        $inventoryManager = $this->user($tenant, 'ROLE_INVENTORY_MANAGER', 'manager@movement-enum.test');

        Livewire::actingAs($inventoryManager)
            ->test(InventoryTracker::class)
            ->set('movementType', 'TELEPORT_STOCK')
            ->call('recordStockMovement')
            ->assertHasErrors('movementType');

        $this->assertDatabaseCount('inventory_logs', 0);
    }

    public function test_min_guest_count_is_persisted_for_menu_items(): void
    {
        $tenant = $this->tenant('menu-min-guest');
        $item = MenuItem::create([
            'tenant_id' => $tenant->id,
            'category' => 'MAINS',
            'name' => 'Chicken Biryani',
            'unit_cost' => 450,
            'min_guest_count' => 80,
        ]);

        $this->assertSame(80, (int) DB::table('menu_items')->where('id', $item->id)->value('min_guest_count'));
        $this->assertTrue($item->fresh()->meetsMinGuestCount(80));
        $this->assertFalse($item->fresh()->meetsMinGuestCount(79));
    }

    public function test_stock_movement_cannot_drive_inventory_below_zero(): void
    {
        $tenant = $this->tenant('negative-stock');
        $inventoryManager = $this->user($tenant, 'ROLE_INVENTORY_MANAGER', 'manager@negative-stock.test');
        $item = InventoryItem::create([
            'tenant_id' => $tenant->id,
            'name' => 'Mutton Cut',
            'category' => 'RAW_FOOD',
            'unit' => 'KG',
            'current_stock' => 5,
            'min_threshold' => 1,
            'unit_cost' => 1000,
        ]);

        Livewire::actingAs($inventoryManager)
            ->test(InventoryTracker::class)
            ->set('movementItemId', $item->id)
            ->set('movementType', 'STOCK_OUT')
            ->set('movementQuantity', 10)
            ->set('movementUnitPrice', 1000)
            ->call('recordStockMovement')
            ->assertHasNoErrors()
            ->assertSet('errorMessage', 'Cannot deduct 10 KG; available stock is only 5 KG.');

        $this->assertSame(5.0, (float) DB::table('inventory_items')->where('id', $item->id)->value('current_stock'));
        $this->assertSame(0, DB::table('inventory_logs')->where('inventory_item_id', $item->id)->count());
    }

    public function test_kot_status_must_be_valid_and_follow_allowed_transitions(): void
    {
        config()->set('platform.kitchen_module_enabled', true);

        $tenant = $this->tenant('kot-transitions');
        $owner = $this->user($tenant, 'ROLE_TENANT_OWNER', 'owner@kot-transitions.test');
        $booking = $this->booking($tenant);

        $kot = KitchenKot::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'kot_number' => 'KOT-HARDEN-0001',
            'guest_count' => 250,
            'preparation_status' => KitchenKot::STATUS_PENDING,
        ]);

        Livewire::actingAs($owner)
            ->test(KitchenKotBoard::class)
            ->call('updateStatus', $kot->id, 'NOT_A_STATUS')
            ->assertHasErrors('status');

        // PENDING may not jump directly to READY.
        Livewire::actingAs($owner)
            ->test(KitchenKotBoard::class)
            ->call('updateStatus', $kot->id, KitchenKot::STATUS_READY)
            ->assertHasErrors('status');

        $this->assertSame(KitchenKot::STATUS_PENDING, $kot->fresh()->preparation_status);

        Livewire::actingAs($owner)
            ->test(KitchenKotBoard::class)
            ->call('updateStatus', $kot->id, KitchenKot::STATUS_PREPARING)
            ->assertHasNoErrors();

        $this->assertSame(KitchenKot::STATUS_PREPARING, $kot->fresh()->preparation_status);
    }

    public function test_kot_regeneration_preserves_preparing_state(): void
    {
        config()->set('platform.kitchen_module_enabled', true);

        $tenant = $this->tenant('kot-regeneration');
        $booking = $this->booking($tenant);
        $kot = KitchenKot::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'kot_number' => 'KOT-HARDEN-PREP-0001',
            'guest_count' => 250,
            'raw_meat_kg' => 55,
            'preparation_status' => KitchenKot::STATUS_PREPARING,
        ]);

        $booking->update(['guest_count' => 300]);
        app(KitchenOrderService::class)->generateKotForBooking($booking->fresh());

        $stored = DB::table('kitchen_kots')->where('id', $kot->id)->first();
        $this->assertSame(1, DB::table('kitchen_kots')->where('booking_id', $booking->id)->count());
        $this->assertSame(KitchenKot::STATUS_PREPARING, $stored->preparation_status);
        $this->assertSame(300, (int) $stored->guest_count);
        $this->assertEquals(66, (float) $stored->raw_meat_kg);
    }

    public function test_floor_plan_rejects_booking_from_another_hall(): void
    {
        $tenant = $this->tenant('floor-cross-hall');
        $owner = $this->user($tenant, 'ROLE_TENANT_OWNER', 'owner@floor-cross-hall.test');
        $booking = $this->booking($tenant);
        $otherHall = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $booking->hall->venue_id,
            'name' => 'Other Hall',
            'slug' => 'other-hall-'.$tenant->slug,
            'min_capacity' => 50,
            'max_capacity' => 250,
            'base_rent' => 50000,
            'is_active' => true,
        ]);

        Livewire::actingAs($owner)
            ->test(FloorPlanDesigner::class)
            ->set('selectedHallId', $otherHall->id)
            ->set('selectedBookingId', $booking->id)
            ->set('planName', 'Cross Hall Layout')
            ->set('elements', [[
                'id' => 'tbl-cross-check',
                'type' => 'TABLE_10',
                'name' => 'Table A',
                'seats' => 10,
                'x' => 100,
                'y' => 100,
            ]])
            ->call('saveFloorPlan')
            ->assertHasErrors('selectedBookingId');

        $this->assertSame(0, DB::table('floor_plans')->where('booking_id', $booking->id)->count());
    }

    public function test_finalised_payroll_cannot_be_opened_for_editing(): void
    {
        $tenant = $this->tenant('payroll-edit-guard');
        $owner = $this->user($tenant, 'ROLE_TENANT_OWNER', 'owner@payroll-edit-guard.test');
        $employee = $this->user($tenant, 'ROLE_STAFF_MEMBER', 'staff@payroll-edit-guard.test');

        $payroll = PayrollModel::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employee->id,
            'month_year' => now()->format('Y-m'),
            'basic_salary' => 50000,
            'net_salary' => 50000,
            'status' => 'APPROVED',
            'payment_method' => 'CASH',
        ]);

        Livewire::actingAs($owner)
            ->test(StaffPayroll::class)
            ->call('openEditModal', (string) $payroll->id)
            ->assertSet('showEditModal', false)
            ->assertSet('errorMessage', 'This payroll is finalised. Reopen it to DRAFT before editing.');
    }

    public function test_leave_reject_guard_enforces_pending_state_and_period_lock(): void
    {
        $tenant = $this->tenant('leave-reject-guard');
        $owner = $this->user($tenant, 'ROLE_TENANT_OWNER', 'owner@leave-reject-guard.test');
        $employee = $this->user($tenant, 'ROLE_CHEF', 'chef@leave-reject-guard.test');

        $leave = StaffLeave::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employee->id,
            'leave_type' => 'CASUAL',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'days' => 1,
            'reason' => 'Family commitment',
            'status' => 'PENDING',
        ]);

        Livewire::actingAs($owner)
            ->test(StaffAttendance::class)
            ->call('rejectLeave', (string) $leave->id);

        $this->assertSame('REJECTED', $leave->fresh()->status);
        $this->assertSame(1, AuditLog::where('tenant_id', $tenant->id)
            ->where('action', 'LEAVE_REJECTED')
            ->count());

        // A settled leave cannot be rejected a second time.
        Livewire::actingAs($owner)
            ->test(StaffAttendance::class)
            ->call('rejectLeave', (string) $leave->id)
            ->assertSet('errorMessage', 'Only pending leave applications can be rejected.');

        $this->assertSame(1, AuditLog::where('tenant_id', $tenant->id)
            ->where('action', 'LEAVE_REJECTED')
            ->count());

        // A locked payroll period also blocks rejection of a still-pending leave.
        PayrollModel::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employee->id,
            'month_year' => now()->format('Y-m'),
            'basic_salary' => 50000,
            'net_salary' => 50000,
            'status' => 'APPROVED',
            'payment_method' => 'CASH',
        ]);

        $lockedLeave = StaffLeave::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employee->id,
            'leave_type' => 'SICK',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'days' => 1,
            'reason' => 'Medical rest',
            'status' => 'PENDING',
        ]);

        Livewire::actingAs($owner)
            ->test(StaffAttendance::class)
            ->call('rejectLeave', (string) $lockedLeave->id)
            ->assertSet('errorMessage', 'Leave cannot modify a locked attendance period. Reopen the payroll first.');

        $this->assertSame('PENDING', $lockedLeave->fresh()->status);
    }

    private function tenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => 'ERP Hardening '.strtoupper($slug),
            'slug' => $slug,
            'email' => $slug.'@example.test',
            'subscription_status' => 'ACTIVE',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);
    }

    private function user(Tenant $tenant, string $role, string $email): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => str_replace('@', ' ', $email),
            'email' => $email,
            'password' => bcrypt('password123'),
            'role' => $role,
            'status' => 'ACTIVE',
        ]);
    }

    private function booking(Tenant $tenant): Booking
    {
        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Hardening Venue',
            'slug' => 'hardening-venue-'.$tenant->slug,
            'city' => 'Lahore',
            'is_active' => true,
        ]);

        $hall = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Hardening Hall',
            'slug' => 'hardening-hall-'.$tenant->slug,
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 100000,
            'is_active' => true,
        ]);

        return Booking::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hall->id,
            'customer_name' => 'Test Host',
            'customer_phone' => '+92 300 0000000',
            'event_date' => now()->format('Y-m-d'),
            'slot_type' => 'EVENING',
            'guest_count' => 250,
            'status' => 'CONFIRMED',
        ]);
    }
}
