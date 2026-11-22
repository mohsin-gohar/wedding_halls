<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Livewire\FloorPlanDesigner;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SaaSTenantIsolationAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_super_admin_can_approve_and_suspend_vendors(): void
    {
        // 1. Create a pending tenant
        $pendingTenant = Tenant::create([
            'name' => 'Royal Marquee Bahria',
            'slug' => 'royal-marquee-bahria',
            'email' => 'bahria@marquee.pk',
            'phone' => '+92 300 1234567',
            'subscription_plan' => 'STARTER',
            'currency' => 'PKR',
            'verification_status' => 'PENDING',
            'is_active' => true,
        ]);

        $this->assertTrue($pendingTenant->isPending());
        $this->assertFalse($pendingTenant->isApproved());

        // 2. Super admin logs in
        $admin = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@riwajgrand.com',
            'phone' => '+92 300 8888888',
            'role' => 'ROLE_SUPER_ADMIN',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($admin);

        // 3. Test Livewire AdminDashboard approval action
        Livewire::test(AdminDashboard::class)
            ->call('approveTenant', $pendingTenant->id)
            ->assertHasNoErrors();

        $pendingTenant->refresh();
        $this->assertEquals('APPROVED', $pendingTenant->verification_status);
        $this->assertTrue($pendingTenant->isApproved());

        // 4. Test Livewire AdminDashboard toggle status action
        Livewire::test(AdminDashboard::class)
            ->call('toggleTenantStatus', $pendingTenant->id)
            ->assertHasNoErrors();

        $pendingTenant->refresh();
        $this->assertFalse($pendingTenant->is_active);
    }

    public function test_strict_cross_tenant_isolation_on_floor_plans(): void
    {
        // Tenant A
        $tenantA = Tenant::first();
        $hallA = Hall::where('tenant_id', $tenantA->id)->first();
        $userA = User::where('tenant_id', $tenantA->id)->where('role', 'ROLE_TENANT_OWNER')->first() ?? User::where('tenant_id', $tenantA->id)->first();

        // Tenant B
        $tenantB = Tenant::create([
            'name' => 'Emerald Palace',
            'slug' => 'emerald-palace',
            'email' => 'emerald@palace.pk',
            'phone' => '+92 321 0000000',
            'subscription_plan' => 'PROFESSIONAL',
            'currency' => 'PKR',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        $venueB = Venue::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Emerald Venue',
            'slug' => 'emerald-venue',
            'city' => 'Islamabad',
            'phone' => '+92 321 0000000',
            'email' => 'emerald@palace.pk',
            'is_active' => true,
        ]);

        $hallB = Hall::create([
            'tenant_id' => $tenantB->id,
            'venue_id' => $venueB->id,
            'name' => 'Emerald Grand Ballroom',
            'min_capacity' => 200,
            'max_capacity' => 1000,
            'base_rent' => 300000,
            'is_active' => true,
        ]);

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Owner B',
            'email' => 'ownerb@palace.pk',
            'phone' => '+92 321 0000001',
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('password123'),
        ]);

        // User A cannot see Hall B in available halls
        $this->actingAs($userA);

        $component = Livewire::test(FloorPlanDesigner::class);
        $halls = $component->viewData('halls');
        $this->assertFalse($halls->contains('id', $hallB->id));
        $this->assertTrue($halls->contains('id', $hallA->id));

        // User A cannot save or overwrite Floor Plan for Hall B (belongs to Tenant B)
        Livewire::test(FloorPlanDesigner::class)
            ->set('selectedHallId', $hallB->id)
            ->set('planName', 'Malicious Injected Layout')
            ->call('saveFloorPlan')
            // Cross-tenant hall IDs are rejected by the tenant-scoped
            // validation rule, so no foreign hall layout can be written.
            ->assertHasErrors('selectedHallId');

        $this->assertDatabaseMissing('floor_plans', [
            'hall_id' => $hallB->id,
            'name' => 'Malicious Injected Layout',
        ]);
    }

    public function test_cross_tenant_document_download_is_forbidden(): void
    {
        // Tenant 1
        $tenant1 = Tenant::first();
        $booking1 = Booking::where('tenant_id', $tenant1->id)->first();

        // Tenant 2
        $tenant2 = Tenant::create([
            'name' => 'Sapphire Marquee',
            'slug' => 'sapphire-marquee',
            'email' => 'sapphire@hall.pk',
            'phone' => '+92 333 1112233',
            'subscription_plan' => 'ENTERPRISE',
            'currency' => 'PKR',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        $user2 = User::create([
            'tenant_id' => $tenant2->id,
            'name' => 'Owner Sapphire',
            'email' => 'sapphire_owner@hall.pk',
            'phone' => '+92 333 1112233',
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('secret123'),
        ]);

        // Owner of Tenant 2 attempts to download Tenant 1's legal agreement -> 403 Forbidden
        $response = $this->actingAs($user2)->get(route('documents.agreement', $booking1->id));
        $response->assertStatus(403);
    }

    public function test_customer_portal_shows_customer_own_bookings(): void
    {
        $tenant = Tenant::first();
        $hall = Hall::where('tenant_id', $tenant->id)->first();

        $customer = User::create([
            'name' => 'Usman Tariq',
            'email' => 'usman.tariq@gmail.com',
            'phone' => '+92 300 4455667',
            'role' => 'ROLE_CUSTOMER',
            'password' => Hash::make('secret123'),
        ]);

        $booking = Booking::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hall->id,
            'customer_id' => $customer->id,
            'booking_reference' => 'BK-CUST-999',
            'customer_name' => 'Usman Tariq',
            'customer_phone' => '+92 300 4455667',
            'event_type' => 'Walima Reception',
            'event_date' => now()->addDays(20)->format('Y-m-d'),
            'slot_type' => 'EVENING',
            'guest_count' => 350,
            'per_head_rate' => 2200,
            'hall_rent' => 150000,
            'grand_total' => 920000,
            'advance_paid' => 300000,
            'balance_due' => 620000,
            'status' => 'CONFIRMED',
            'pipeline_stage' => 'CONFIRMED',
        ]);

        // Customer logs in and accesses dashboard
        $response = $this->actingAs($customer)->get(route('customer.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Walima Reception');
        $response->assertSee('BK-CUST-999');

        // Customer can download their own booking contract
        $docRes = $this->actingAs($customer)->get(route('documents.agreement', $booking->id));
        $docRes->assertStatus(200);
    }
}
