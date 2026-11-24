<?php

namespace Tests\Feature;

use App\Livewire\Auth\Register;
use App\Livewire\CustomerEventWorkspace;
use App\Livewire\SupportCenter;
use App\Livewire\VendorFinanceBoard;
use App\Livewire\VendorLocationsManager;
use App\Models\ApiToken;
use App\Models\CustomerEvent;
use App\Models\CustomerEventTask;
use App\Models\Hall;
use App\Models\ServiceBooking;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorLocation;
use App\Support\PermissionMatrix;
use App\Support\TenantBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Enterprise expansion coverage:
 *  - RBAC separation of duties (platform support / finance / moderation)
 *  - API authorization must fail closed (no cross-tenant leakage)
 *  - Multi-location businesses
 *  - Customer event workspace isolation
 *  - Vendor commission / payout guardrails
 */
class EnterpriseExpansionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function cateringVendor(): Tenant
    {
        return Tenant::query()->where('slug', 'nawab-caterers')->firstOrFail();
    }

    private function photographyVendor(): Tenant
    {
        return Tenant::query()->where('slug', 'lens-and-lantern-studio')->firstOrFail();
    }

    private function vendorOwner(Tenant $tenant): User
    {
        return User::query()->where('tenant_id', $tenant->id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'ROLE_CUSTOMER']);
    }

    private function platformStaff(string $role): User
    {
        $user = User::factory()->create(['role' => $role, 'tenant_id' => null]);
        $user->assignRole($role);

        return $user->fresh();
    }

    // ─────────────────────────────────────────────────────────────
    // RBAC — separation of duties
    // ─────────────────────────────────────────────────────────────

    public function test_permission_matrix_covers_the_enterprise_role_vocabulary(): void
    {
        $roles = PermissionMatrix::allRoles();

        foreach ([
            'ROLE_SUPER_ADMIN',
            'ROLE_PLATFORM_SUPPORT',
            'ROLE_PLATFORM_FINANCE',
            'ROLE_CONTENT_MODERATOR',
            'ROLE_VENDOR_OWNER',
            'ROLE_VENDOR_MANAGER',
            'ROLE_VENDOR_ACCOUNTANT',
            'ROLE_BOOKING_MANAGER',
            'ROLE_STAFF_MEMBER',
            'ROLE_PHOTOGRAPHER',
            'ROLE_CATERING_STAFF',
            'ROLE_INVENTORY_MANAGER',
            'ROLE_HR_MANAGER',
            'ROLE_CUSTOMER',
        ] as $role) {
            $this->assertContains($role, $roles, "Missing enterprise role {$role}");
        }

        $this->assertGreaterThan(100, count(PermissionMatrix::allPermissions()));
    }

    public function test_platform_support_cannot_reach_financial_administration(): void
    {
        $support = $this->platformStaff('ROLE_PLATFORM_SUPPORT');

        $permissions = PermissionMatrix::permissionsForRole('ROLE_PLATFORM_SUPPORT');

        $this->assertNotContains('payouts.approve', $permissions);
        $this->assertNotContains('commissions.manage', $permissions);
        $this->assertNotContains('roles.manage', $permissions);
        $this->assertNotContains('settings.manage', $permissions);
        $this->assertContains('support.view', $permissions);

        $this->assertTrue($support->hasPermissionTo('support.view'));
        $this->assertFalse($support->hasPermissionTo('payouts.approve'));
    }

    public function test_platform_finance_cannot_manage_roles_or_users(): void
    {
        $permissions = PermissionMatrix::permissionsForRole('ROLE_PLATFORM_FINANCE');

        $this->assertContains('payouts.approve', $permissions);
        $this->assertContains('commissions.view', $permissions);
        $this->assertNotContains('roles.manage', $permissions);
        $this->assertNotContains('staff.manage', $permissions);
        $this->assertNotContains('settings.manage', $permissions);
    }

    public function test_content_moderator_cannot_touch_financial_records(): void
    {
        $permissions = PermissionMatrix::permissionsForRole('ROLE_CONTENT_MODERATOR');

        $this->assertContains('listings.moderate', $permissions);
        $this->assertContains('reviews.moderate', $permissions);
        $this->assertNotContains('payments.view', $permissions);
        $this->assertNotContains('payouts.view', $permissions);
        $this->assertNotContains('commissions.view', $permissions);
    }

    public function test_vendor_owner_alias_role_inherits_tenant_owner_abilities(): void
    {
        $owner = $this->vendorOwner($this->cateringVendor());

        $alias = User::factory()->create([
            'role' => 'ROLE_VENDOR_OWNER',
            'tenant_id' => $owner->tenant_id,
        ]);
        $alias->assignRole('ROLE_VENDOR_OWNER');

        $this->assertTrue($alias->isTenantOwner());
        $this->assertTrue($alias->hasPermissionTo('locations.manage'));
        $this->assertTrue($alias->hasPermissionTo('payouts.request'));
        $this->assertFalse($alias->isCustomer());
    }

    public function test_staff_manager_cannot_assign_platform_roles(): void
    {
        $assignable = PermissionMatrix::assignableVendorRoles();

        $this->assertNotContains('ROLE_SUPER_ADMIN', $assignable);
        $this->assertNotContains('ROLE_PLATFORM_SUPPORT', $assignable);
        $this->assertNotContains('ROLE_PLATFORM_FINANCE', $assignable);
        $this->assertNotContains('ROLE_CONTENT_MODERATOR', $assignable);
        $this->assertContains('ROLE_PHOTOGRAPHER', $assignable);
    }

    public function test_public_registration_only_assigns_vendor_owner_role(): void
    {
        $this->assertSame(['ROLE_TENANT_OWNER', 'ROLE_VENDOR_OWNER'], PermissionMatrix::publicRegistrationRoles());

        Livewire::actingAs(new User)
            ->test(Register::class)
            ->set('hallName', 'Aurora Marquee')
            ->set('ownerName', 'Imran Sheikh')
            ->set('email', 'newvendor@example.com')
            ->set('phone', '+92 300 1234567')
            ->set('city', 'Karachi')
            ->set('capacity', 400)
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('register')
            ->assertHasNoErrors();

        $user = User::query()->where('email', 'newvendor@example.com')->firstOrFail();

        $this->assertSame('ROLE_TENANT_OWNER', $user->role);
        $this->assertNotSame('ROLE_SUPER_ADMIN', $user->role);
        $this->assertFalse($user->isSuperAdmin());

        // The tenant must start unverified so the platform can vet it.
        $this->assertSame('PENDING', $user->tenant->verification_status);
    }

    // ─────────────────────────────────────────────────────────────
    // API — fail closed, no cross-tenant leakage
    // ─────────────────────────────────────────────────────────────

    public function test_api_rejects_unauthenticated_requests_instead_of_leaking_data(): void
    {
        $this->getJson('/api/v1/bookings')->assertStatus(401);
        $this->getJson('/api/v1/payments')->assertStatus(401);
        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_api_tenant_staff_only_sees_their_own_tenant_bookings(): void
    {
        $owner = $this->vendorOwner($this->cateringVendor());
        $other = $this->vendorOwner($this->photographyVendor());

        ServiceBooking::create([
            'tenant_id' => $owner->tenant_id,
            'vendor_category_id' => $owner->tenant->vendor_category_id,
            'customer_id' => $this->customer()->id,
            'booking_reference' => 'RG-SB-OWNERTENANT',
            'event_name' => 'Catering mega event',
            'event_date' => now()->addDays(40)->toDateString(),
            'quoted_amount' => 500000,
            'status' => 'ACCEPTED',
        ]);

        ServiceBooking::create([
            'tenant_id' => $other->tenant_id,
            'vendor_category_id' => $other->tenant->vendor_category_id,
            'customer_id' => $this->customer()->id,
            'booking_reference' => 'RG-SB-OTHERTENANT',
            'event_name' => 'Photography secret shoot',
            'event_date' => now()->addDays(40)->toDateString(),
            'quoted_amount' => 120000,
            'status' => 'ACCEPTED',
        ]);

        $response = $this->actingAs($owner)->getJson('/api/v1/service-bookings')->assertOk();

        $this->assertStringContainsString('Catering mega event', $response->getContent());
        $this->assertStringNotContainsString('Photography secret shoot', $response->getContent());
    }

    public function test_api_customer_only_sees_their_own_records(): void
    {
        $customer = $this->customer();
        $otherCustomer = $this->customer();

        $owner = $this->vendorOwner($this->cateringVendor());

        ServiceBooking::create([
            'tenant_id' => $owner->tenant_id,
            'vendor_category_id' => $owner->tenant->vendor_category_id,
            'customer_id' => $customer->id,
            'booking_reference' => 'RG-SB-MINE',
            'event_name' => 'My own event booking',
            'event_date' => now()->addDays(30)->toDateString(),
            'quoted_amount' => 300000,
            'status' => 'ACCEPTED',
        ]);

        ServiceBooking::create([
            'tenant_id' => $owner->tenant_id,
            'vendor_category_id' => $owner->tenant->vendor_category_id,
            'customer_id' => $otherCustomer->id,
            'booking_reference' => 'RG-SB-NOTMINE',
            'event_name' => 'Someone else booking',
            'event_date' => now()->addDays(30)->toDateString(),
            'quoted_amount' => 400000,
            'status' => 'ACCEPTED',
        ]);

        $response = $this->actingAs($customer)->getJson('/api/v1/service-bookings')->assertOk();

        $this->assertStringContainsString('My own event booking', $response->getContent());
        $this->assertStringNotContainsString('Someone else booking', $response->getContent());
    }

    public function test_api_bearer_token_authenticates_a_mobile_client(): void
    {
        $owner = $this->vendorOwner($this->cateringVendor());

        [$token, $plain] = ApiToken::issue($owner, 'Mobile app');

        $this->assertDatabaseHas('api_tokens', ['token_hash' => hash('sha256', $plain)]);
        $this->assertDatabaseMissing('api_tokens', ['token_hash' => $plain]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/me')
            ->assertOk();

        $this->assertSame($owner->id, $response->json('id'));
        $this->assertSame($owner->email, $response->json('email'));
    }

    public function test_revoked_api_token_is_rejected(): void
    {
        $owner = $this->vendorOwner($this->cateringVendor());

        [$token, $plain] = ApiToken::issue($owner, 'Temp token');
        $token->revoke();

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/me')
            ->assertStatus(401);
    }

    // ─────────────────────────────────────────────────────────────
    // Multi-location businesses
    // ─────────────────────────────────────────────────────────────

    public function test_vendor_can_manage_multi_location_branches(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);

        Livewire::actingAs($owner)
            ->test(VendorLocationsManager::class)
            ->set('locationName', 'DHA Production Kitchen')
            ->set('branchType', 'BRANCH')
            ->set('city', 'Karachi')
            ->set('area', 'DHA Phase 6')
            ->set('isPrimary', true)
            ->call('saveLocation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vendor_locations', [
            'tenant_id' => $vendor->id,
            'name' => 'DHA Production Kitchen',
            'branch_type' => 'BRANCH',
            'city' => 'Karachi',
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    public function test_location_requires_a_branch_name(): void
    {
        $owner = $this->vendorOwner($this->cateringVendor());

        Livewire::actingAs($owner)
            ->test(VendorLocationsManager::class)
            ->set('locationName', '')
            ->call('saveLocation')
            ->assertHasErrors(['locationName']);
    }

    public function test_vendor_cannot_see_or_edit_another_vendors_locations(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);
        $otherVendor = $this->photographyVendor();
        $otherOwner = $this->vendorOwner($otherVendor);

        Livewire::actingAs($owner)
            ->test(VendorLocationsManager::class)
            ->set('locationName', 'PECHS Central Kitchen')
            ->set('city', 'Karachi')
            ->call('saveLocation')
            ->assertHasNoErrors();

        $location = VendorLocation::query()->where('tenant_id', $vendor->id)->firstOrFail();

        // The other vendor's board must not contain this location.
        Livewire::actingAs($otherOwner)
            ->test(VendorLocationsManager::class)
            ->assertDontSee('PECHS Central Kitchen');

        // And a direct edit attempt must not reach the record.
        Livewire::actingAs($otherOwner)
            ->test(VendorLocationsManager::class)
            ->call('openEditModal', $location->id)
            ->assertSet('locationName', '');

        $this->assertSame('PECHS Central Kitchen', $location->fresh()->name);
    }

    public function test_locations_page_requires_vendor_staff(): void
    {
        $this->get('/vendor/locations')->assertRedirect(route('login'));
        $this->actingAs($this->customer())->get('/vendor/locations')->assertRedirect(route('customer.dashboard'));
        $this->actingAs($this->vendorOwner($this->cateringVendor()))->get('/vendor/locations')->assertOk();
    }

    // ─────────────────────────────────────────────────────────────
    // Customer event workspace
    // ─────────────────────────────────────────────────────────────

    public function test_customer_can_create_an_event_workspace_entry(): void
    {
        $customer = $this->customer();

        Livewire::actingAs($customer)
            ->test(CustomerEventWorkspace::class)
            ->set('eventName', 'Ayesha & Bilal — Walima')
            ->set('eventType', 'WALIMA')
            ->set('eventDate', now()->addDays(90)->toDateString())
            ->set('eventCity', 'Lahore')
            ->set('guestCount', 450)
            ->set('estimatedBudget', 2500000)
            ->call('saveEvent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customer_events', [
            'user_id' => $customer->id,
            'name' => 'Ayesha & Bilal — Walima',
            'event_type' => 'WALIMA',
            'guest_count' => 450,
            'estimated_budget' => 2500000,
        ]);
    }

    public function test_customer_cannot_open_another_customers_event(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer();

        $event = CustomerEvent::create([
            'user_id' => $owner->id,
            'name' => 'Private wedding plan',
            'event_type' => 'WEDDING',
            'event_date' => now()->addDays(60)->toDateString(),
            'status' => 'PLANNING',
        ]);

        Livewire::actingAs($intruder)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $event->id);

        $this->assertNull(
            CustomerEvent::query()
                ->where('id', $event->id)
                ->where('user_id', $intruder->id)
                ->first()
        );
    }

    public function test_customer_event_workspace_requires_a_customer_account(): void
    {
        // Vendor staff are bounced out of the customer portal.
        $owner = $this->vendorOwner($this->cateringVendor());

        $this->actingAs($owner)
            ->get('/customer/events')
            ->assertRedirect(route($owner->portalHomeRoute()));

        $this->actingAs($this->customer())
            ->get('/customer/events')
            ->assertOk();
    }

    public function test_customer_can_add_and_complete_tasks_on_their_event(): void
    {
        $customer = $this->customer();

        $event = CustomerEvent::create([
            'user_id' => $customer->id,
            'name' => 'Mehndi night',
            'event_type' => 'MEHNDI',
            'event_date' => now()->addDays(30)->toDateString(),
            'status' => 'PLANNING',
        ]);

        Livewire::actingAs($customer)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $event->id)
            ->set('taskTitle', 'Finalise dhol ensemble')
            ->call('addTask')
            ->assertHasNoErrors();

        $task = CustomerEventTask::query()
            ->where('customer_event_id', $event->id)
            ->firstOrFail();

        $this->assertSame('PENDING', $task->status);

        Livewire::actingAs($customer)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $event->id)
            ->call('toggleTask', $task->id);

        $this->assertSame('DONE', $task->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────
    // Vendor commission + payout guardrails
    // ─────────────────────────────────────────────────────────────

    public function test_finance_board_is_scoped_to_the_signed_in_vendor(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);
        $otherOwner = $this->vendorOwner($this->photographyVendor());

        ServiceBooking::create([
            'tenant_id' => $vendor->id,
            'vendor_category_id' => $vendor->vendor_category_id,
            'customer_id' => $this->customer()->id,
            'booking_reference' => 'RG-SB-COMMISSION',
            'event_name' => 'Large catering event',
            'event_date' => now()->addDays(50)->toDateString(),
            'quoted_amount' => 1000000,
            'commission_amount' => 50000,
            'net_vendor_amount' => 950000,
            'paid_amount' => 1000000,
            'status' => 'CONFIRMED',
            'payment_status' => 'PAID',
        ]);

        $this->actingAs($owner)->get('/vendor/finance')->assertOk()->assertSee('1,000,000');
        $this->actingAs($otherOwner)->get('/vendor/finance')->assertOk()->assertDontSee('1,000,000');
    }

    public function test_payout_request_cannot_exceed_the_available_wallet_balance(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);

        Livewire::actingAs($owner)
            ->test(VendorFinanceBoard::class)
            ->set('payoutAmount', 500000)
            ->set('payoutAccountName', 'Nawab Caterers')
            ->call('requestPayout')
            ->assertHasErrors(['payoutAmount']);

        $this->assertDatabaseCount('vendor_payouts', 0);
    }

    public function test_finance_page_requires_vendor_staff(): void
    {
        $this->get('/vendor/finance')->assertRedirect(route('login'));
        $this->actingAs($this->customer())->get('/vendor/finance')->assertRedirect(route('customer.dashboard'));
        $this->actingAs($this->vendorOwner($this->cateringVendor()))->get('/vendor/finance')->assertOk();
    }

    // ─────────────────────────────────────────────────────────────
    // Route integrity (regression guards for the audit findings)
    // ─────────────────────────────────────────────────────────────

    public function test_legacy_dashboard_route_name_still_resolves(): void
    {
        $this->assertSame(url('/dashboard'), route('dashboard'));
        $this->assertSame(url('/customer/dashboard'), route('customer.dashboard'));
    }

    public function test_vendor_dashboard_is_not_shadowed_by_the_public_vendor_catch_all(): void
    {
        $owner = $this->vendorOwner($this->cateringVendor());

        // /vendor/dashboard must reach the ERP dashboard, never the public
        // vendor profile (which would render a 200 for a non-vendor slug).
        $this->actingAs($owner)
            ->get('/vendor/dashboard')
            ->assertOk()
            ->assertDontSee('request-quote');
    }

    public function test_api_availability_route_is_not_shadowed_by_booking_id_route(): void
    {
        $owner = $this->vendorOwner($this->cateringVendor());

        $response = $this->actingAs($owner)
            ->getJson('/api/v1/bookings/availability?hall_id='.Hall::query()->where('tenant_id', $owner->tenant_id)->firstOrFail()->id.'&date='.now()->addDays(10)->toDateString());

        $response->assertOk();
        $this->assertArrayHasKey('available', $response->json());
    }

    // ─────────────────────────────────────────────────────────────
    // White-label vendor websites
    // ─────────────────────────────────────────────────────────────

    public function test_vendor_white_label_site_renders_with_its_own_branding(): void
    {
        $vendor = $this->cateringVendor();

        $this->get('/site/'.$vendor->slug)
            ->assertOk()
            ->assertSee($vendor->name)
            ->assertSee('Our Services')
            ->assertSee('Request a Quotation');
    }

    public function test_white_label_site_returns_404_for_an_unknown_business(): void
    {
        $this->get('/site/does-not-exist-vendor')->assertNotFound();
    }

    public function test_white_label_site_is_hidden_for_an_inactive_business(): void
    {
        $vendor = $this->cateringVendor();
        $vendor->update(['is_active' => false]);

        $this->get('/site/'.$vendor->slug)->assertNotFound();
    }

    public function test_brand_resolves_from_a_vendor_subdomain(): void
    {
        $vendor = $this->cateringVendor();
        $vendor->update(['subdomain' => 'nawabcaterers']);

        $brand = TenantBrand::resolveFromHost('nawabcaterers.riwajgrand.pk', 'riwajgrand.pk');

        $this->assertNotNull($brand);
        $this->assertSame($vendor->id, $brand->tenant->id);
        $this->assertStringContainsString('nawabcaterers.riwajgrand.pk', $brand->siteUrl());

        // The platform root domain must never resolve to a tenant brand.
        $this->assertNull(TenantBrand::resolveFromHost('riwajgrand.pk', 'riwajgrand.pk'));
        $this->assertNull(TenantBrand::resolveFromHost('unknown.riwajgrand.pk', 'riwajgrand.pk'));
        $this->assertNull(TenantBrand::resolveFromHost('evil.example.com', 'riwajgrand.pk'));
    }

    public function test_brand_uses_the_tenant_website_settings_overrides(): void
    {
        $vendor = $this->cateringVendor();
        $vendor->update([
            'website_settings' => [
                'brand_name' => 'Nawab Fine Catering',
                'primary_color' => '#101010',
                'accent_color' => '#D4AF37',
            ],
        ]);

        $brand = TenantBrand::for($vendor->fresh());

        $this->assertSame('Nawab Fine Catering', $brand->name);
        $this->assertSame('#101010', $brand->primaryColor);
        $this->assertSame('#D4AF37', $brand->accentColor);
    }

    // ─────────────────────────────────────────────────────────────
    // Support center
    // ─────────────────────────────────────────────────────────────

    public function test_customer_can_raise_a_support_ticket(): void
    {
        $customer = $this->customer();

        Livewire::actingAs($customer)
            ->test(SupportCenter::class)
            ->set('subject', 'Advance payment not reflected')
            ->set('category', 'PAYMENT')
            ->set('priority', 'HIGH')
            ->set('body', 'I paid the advance two days ago but my booking still shows unpaid.')
            ->call('createTicket')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $customer->id,
            'subject' => 'Advance payment not reflected',
            'category' => 'PAYMENT',
            'status' => 'OPEN',
        ]);

        $this->assertDatabaseHas('ticket_messages', ['user_id' => $customer->id]);
    }

    public function test_customer_cannot_read_another_customers_ticket(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer();

        $ticket = SupportTicket::create([
            'ticket_no' => SupportTicket::generateTicketNo(),
            'user_id' => $owner->id,
            'subject' => 'Confidential complaint',
            'category' => 'DISPUTE',
            'priority' => 'URGENT',
            'status' => 'OPEN',
        ]);

        Livewire::actingAs($intruder)
            ->test(SupportCenter::class)
            ->assertDontSee('Confidential complaint');

        Livewire::actingAs($intruder)
            ->test(SupportCenter::class)
            ->call('openTicket', $ticket->id);

        $this->assertNull(
            SupportTicket::query()
                ->where('id', $ticket->id)
                ->where('user_id', $intruder->id)
                ->first()
        );
    }

    public function test_platform_support_staff_can_see_every_ticket(): void
    {
        $customer = $this->customer();
        $support = $this->platformStaff('ROLE_PLATFORM_SUPPORT');

        SupportTicket::create([
            'ticket_no' => SupportTicket::generateTicketNo(),
            'user_id' => $customer->id,
            'subject' => 'Platform wide complaint',
            'category' => 'GENERAL',
            'priority' => 'MEDIUM',
            'status' => 'OPEN',
        ]);

        Livewire::actingAs($support)
            ->test(SupportCenter::class)
            ->assertSee('Platform wide complaint');
    }

    public function test_support_page_requires_authentication(): void
    {
        $this->get('/account/support')->assertRedirect(route('login'));
        $this->actingAs($this->customer())->get('/account/support')->assertOk();
    }
}
