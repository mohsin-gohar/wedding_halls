<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Livewire\ExpenseTracker;
use App\Livewire\StaffManager;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SaaSSubscriptionAndErpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_tenant_isolation_on_expenses_and_staff(): void
    {
        // 1. Create Tenant A
        $tenantA = Tenant::create([
            'name' => 'Royal Pearl Banquet',
            'slug' => 'royal-pearl',
            'email' => 'pearl@tenant-a.com',
            'phone' => '+92 300 1111111',
            'is_active' => true,
            'verification_status' => 'APPROVED',
        ]);
        $ownerA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Owner A',
            'email' => 'ownerA@pearl.com',
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('secret123'),
        ]);

        // 2. Create Tenant B
        $tenantB = Tenant::create([
            'name' => 'Imperial Crown Banquet',
            'slug' => 'imperial-crown',
            'email' => 'crown@tenant-b.com',
            'phone' => '+92 300 2222222',
            'is_active' => true,
            'verification_status' => 'APPROVED',
        ]);
        $ownerB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Owner B',
            'email' => 'ownerB@crown.com',
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('secret123'),
        ]);

        // Tenant A creates an expense
        $expenseA = Expense::create([
            'tenant_id' => $tenantA->id,
            'category' => 'GENERATOR_DIESEL',
            'title' => 'Secret Diesel Refuel for Tenant A',
            'amount' => 75000.00,
            'expense_date' => Carbon::now()->format('Y-m-d'),
            'payee' => 'Fuel Vendor A',
            'payment_method' => 'CASH',
            'created_by_user_id' => $ownerA->id,
        ]);

        // Tenant A creates a staff member
        $staffA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Staff Specialist A',
            'email' => 'specialistA@pearl.com',
            'role' => 'ROLE_FRONT_DESK',
            'password' => Hash::make('secret123'),
        ]);

        // Acting as Owner B: inspect ExpenseTracker
        $this->actingAs($ownerB);
        $expenseComponent = Livewire::test(ExpenseTracker::class);
        $viewExpenses = $expenseComponent->viewData('expenses');

        // Verify Owner B cannot see Tenant A's expense
        $this->assertFalse($viewExpenses->contains('id', $expenseA->id));
        $this->assertFalse($viewExpenses->contains('title', 'Secret Diesel Refuel for Tenant A'));

        // Acting as Owner B: inspect StaffManager
        $staffComponent = Livewire::test(StaffManager::class);
        $viewStaff = $staffComponent->viewData('staffMembers');

        // Verify Owner B cannot see Tenant A's staff
        $this->assertFalse($viewStaff->contains('id', $staffA->id));
        $this->assertFalse($viewStaff->contains('email', 'specialistA@pearl.com'));

        // Owner B tries to delete Tenant A's expense
        try {
            $expenseComponent->call('deleteExpense', $expenseA->id);
        } catch (\Throwable $e) {
            // Expected exception due to tenant scoping
        }

        // Verify Tenant A's expense was NOT deleted and remains protected
        $this->assertDatabaseHas('expenses', [
            'id' => $expenseA->id,
            'tenant_id' => $tenantA->id,
            'title' => 'Secret Diesel Refuel for Tenant A',
        ]);
    }

    public function test_super_admin_can_manage_subscription_plans_and_approve_payments(): void
    {
        $superAdmin = User::where('role', 'ROLE_SUPER_ADMIN')->first();
        $this->assertNotNull($superAdmin);

        $tenant = Tenant::first();
        $this->assertNotNull($tenant);

        $plan = SubscriptionPlan::where('slug', 'royal-imperial-enterprise')->first();
        $this->assertNotNull($plan);

        // Create pending subscription payment
        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'billing_cycle' => 'YEARLY',
            'amount' => 650000.00,
            'payment_method' => 'BANK_TRANSFER',
            'transaction_reference' => 'HBL-FT-SaaS-9901',
            'status' => 'PENDING',
        ]);

        $this->actingAs($superAdmin);

        // Super Admin approves payment
        Livewire::test(AdminDashboard::class)
            ->call('approveSubscriptionPayment', $payment->id)
            ->assertSee('Subscription payment approved');

        // Verify payment is approved
        $payment->refresh();
        $this->assertEquals('APPROVED', $payment->status);
        $this->assertEquals($superAdmin->id, $payment->approved_by_user_id);

        // Verify tenant subscription is updated
        $tenant->refresh();
        $this->assertEquals($plan->id, $tenant->subscription_plan_id);
        $this->assertEquals('ACTIVE', $tenant->subscription_status);
        $this->assertTrue($tenant->subscription_expires_at->isFuture());
    }

    public function test_staff_role_permissions_enforced(): void
    {
        $chef = User::where('role', 'ROLE_CHEF')->first();
        $this->assertNotNull($chef);

        // Chef attempts to access StaffManager -> 403 Forbidden
        $this->actingAs($chef);
        $this->get(route('staff'))->assertStatus(403);

        // Owner accesses StaffManager -> 200 OK
        $owner = User::where('role', 'ROLE_SUPER_ADMIN')->first();
        $this->actingAs($owner);
        $this->get(route('staff'))->assertStatus(200);
    }

    public function test_quotation_and_invoice_routes_render_authorized(): void
    {
        $booking = Booking::first();
        $this->assertNotNull($booking);

        $tenantOwner = User::where('tenant_id', $booking->tenant_id)->first();
        $this->assertNotNull($tenantOwner);

        // Authorized user can view quotation & invoice
        $this->actingAs($tenantOwner);
        $this->get(route('documents.quotation', $booking->id))->assertStatus(200);
        $this->get(route('documents.invoice', $booking->id))->assertStatus(200);

        // Unauthorized guest from another tenant is forbidden
        $otherTenant = Tenant::create([
            'name' => 'Other Venue',
            'slug' => 'other-venue',
            'email' => 'other@venue.com',
            'phone' => '+92 300 3333333',
        ]);
        $otherUser = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign User',
            'email' => 'foreign@other.com',
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($otherUser);
        $this->get(route('documents.quotation', $booking->id))->assertStatus(403);
        $this->get(route('documents.invoice', $booking->id))->assertStatus(403);
    }
}
