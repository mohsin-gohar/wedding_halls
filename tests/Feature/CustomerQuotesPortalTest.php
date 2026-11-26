<?php

namespace Tests\Feature;

use App\Livewire\CustomerDashboard;
use App\Models\RFQ;
use App\Models\RFQBid;
use App\Models\ServiceBooking;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 3 — customer portal visibility of the marketplace funnel.
 *
 * Customers can submit RFQs and receive quotes, but had no page showing
 * the responses or the resulting ServiceBookings. This suite covers the
 * new "My Quotes & Services" tab and — critically — that both lists are
 * scoped to the signed-in customer (a second customer's RFQs, bids and
 * service bookings must never appear).
 */
class CustomerQuotesPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'ROLE_CUSTOMER']);
    }

    private function vendor(string $categorySlug): Tenant
    {
        $category = VendorCategory::query()->where('slug', $categorySlug)->firstOrFail();

        return Tenant::query()
            ->where('vendor_category_id', $category->id)
            ->where('marketplace_visible', true)
            ->firstOrFail();
    }

    private function makeRfq(User $client, Tenant $vendor, string $title): RFQ
    {
        return RFQ::create([
            'client_id' => $client->id,
            'tenant_id' => $vendor->id,
            'vendor_category_id' => $vendor->vendor_category_id,
            'title' => $title,
            'event_date' => now()->addDays(45)->toDateString(),
            'city' => 'Lahore',
            'guest_count' => 250,
            'budget_range' => 'PKR 300k - 500k',
            'requirements' => 'Full coverage with two photographers and an album.',
            'status' => 'OPEN',
        ]);
    }

    private function makeBid(RFQ $rfq, float $amount, string $status = 'PENDING'): RFQBid
    {
        $bidder = User::query()->where('role', '!=', 'ROLE_CUSTOMER')->first()
            ?? User::factory()->create(['role' => 'ROLE_CUSTOMER']);

        return RFQBid::create([
            'rfq_id' => $rfq->id,
            'vendor_id' => $bidder->id,
            'bid_amount' => $amount,
            'proposal' => 'We propose a complete package covering setup, coverage and delivery.',
            'status' => $status,
        ]);
    }

    private function makeServiceBooking(User $customer, Tenant $vendor, string $reference): ServiceBooking
    {
        return ServiceBooking::create([
            'tenant_id' => $vendor->id,
            'vendor_category_id' => $vendor->vendor_category_id,
            'customer_id' => $customer->id,
            'booking_reference' => $reference,
            'event_name' => 'My Grand Walima',
            'event_date' => now()->addDays(60)->toDateString(),
            'quoted_amount' => 150000,
            'status' => 'CONFIRMED',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Tab structure
    // ─────────────────────────────────────────────────────────────

    public function test_quotes_tab_exists_and_renders_for_a_customer(): void
    {
        $customer = $this->customer();

        // The tab button is present on the default (bookings) render…
        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('My Quotes & Services');

        // …and its panel renders once the tab is active.
        Livewire::actingAs($customer)
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'quotes')
            ->assertSee('My Quote Requests')
            ->assertSee('My Service Bookings');
    }

    public function test_switch_tab_accepts_the_quotes_tab(): void
    {
        Livewire::actingAs($this->customer())
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'quotes')
            ->assertSet('activeTab', 'quotes');
    }

    public function test_switch_tab_still_rejects_unknown_tabs(): void
    {
        Livewire::actingAs($this->customer())
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'admin')
            ->assertSet('activeTab', 'bookings');
    }

    public function test_vendor_staff_cannot_call_customer_dashboard_mutations(): void
    {
        $vendor = $this->vendor('catering');
        $staff = User::query()->where('tenant_id', $vendor->id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();

        Livewire::actingAs($staff)
            ->test(CustomerDashboard::class)
            ->call('updateProfile')
            ->assertForbidden();

        Livewire::actingAs($staff)
            ->test(CustomerDashboard::class)
            ->call('acceptQuote', 1)
            ->assertForbidden();
    }

    public function test_platform_admin_cannot_call_customer_dashboard_mutations(): void
    {
        $admin = User::factory()->create([
            'role' => 'ROLE_SUPER_ADMIN',
            'tenant_id' => null,
            'status' => 'ACTIVE',
        ]);
        $admin->assignRole('ROLE_SUPER_ADMIN');

        Livewire::actingAs($admin)
            ->test(CustomerDashboard::class)
            ->call('updateProfile')
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test(CustomerDashboard::class)
            ->call('acceptQuote', 1)
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_the_dashboard(): void
    {
        $this->get(route('customer.dashboard'))->assertRedirect(route('login'));
    }

    // ─────────────────────────────────────────────────────────────
    // Visibility of own RFQs, quotes and service bookings
    // ─────────────────────────────────────────────────────────────

    public function test_customer_sees_their_own_rfqs_with_quote_responses(): void
    {
        $customer = $this->customer();
        $vendor = $this->vendor('catering');

        $rfq = $this->makeRfq($customer, $vendor, 'Baraat Photography Coverage');
        $this->makeBid($rfq, 185000, 'AWARDED');

        Livewire::actingAs($customer)
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'quotes')
            ->assertSee('Baraat Photography Coverage')
            ->assertSee('185,000')
            ->assertSee('Awarded');
    }

    public function test_customer_sees_their_own_service_bookings(): void
    {
        $customer = $this->customer();
        $vendor = $this->vendor('photography');

        $this->makeServiceBooking($customer, $vendor, 'RG-SB-CUST001');

        Livewire::actingAs($customer)
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'quotes')
            ->assertSee('RG-SB-CUST001')
            ->assertSee('My Grand Walima')
            ->assertSee('CONFIRMED');
    }

    public function test_empty_state_shows_helpful_copy_when_no_quotes_exist(): void
    {
        Livewire::actingAs($this->customer())
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'quotes')
            ->assertSee("You haven't requested any quotes yet.", false)
            ->assertSee('No service bookings yet');
    }

    // ─────────────────────────────────────────────────────────────
    // Cross-customer isolation (the core guarantee)
    // ─────────────────────────────────────────────────────────────

    public function test_another_customers_rfqs_and_quotes_never_leak(): void
    {
        $me = $this->customer();
        $intruder = $this->customer();
        $vendor = $this->vendor('decoration');

        $theirRfq = $this->makeRfq($intruder, $vendor, 'Secret Stage Décor RFQ');
        $this->makeBid($theirRfq, 999999, 'AWARDED');

        Livewire::actingAs($me)
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'quotes')
            ->assertDontSee('Secret Stage Décor RFQ')
            ->assertDontSee('999,999')
            ->assertSee("You haven't requested any quotes yet.", false);
    }

    public function test_another_customers_service_bookings_never_leak(): void
    {
        $me = $this->customer();
        $intruder = $this->customer();
        $vendor = $this->vendor('catering');

        $this->makeServiceBooking($intruder, $vendor, 'RG-SB-SECRET9');

        Livewire::actingAs($me)
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'quotes')
            ->assertDontSee('RG-SB-SECRET9')
            ->assertSee('No service bookings yet');
    }

    public function test_dashboard_counts_only_include_the_signed_in_customers_items(): void
    {
        $me = $this->customer();
        $intruder = $this->customer();
        $vendor = $this->vendor('catering');

        $mine = $this->makeRfq($me, $vendor, 'My Own RFQ');
        $this->makeBid($mine, 250000);
        $this->makeRfq($intruder, $vendor, 'Not My RFQ');
        $this->makeServiceBooking($me, $vendor, 'RG-SB-MINE01');
        $this->makeServiceBooking($intruder, $vendor, 'RG-SB-THEIRS');

        $count = Livewire::actingAs($me)
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'quotes')
            ->html();

        // 1 RFQ + 1 service booking of my own = tab count of 2.
        $this->assertStringContainsString('My Quotes &amp; Services (2)', $count);
        $this->assertStringNotContainsString('Not My RFQ', $count);
        $this->assertStringNotContainsString('RG-SB-THEIRS', $count);
    }

    // ─────────────────────────────────────────────────────────────
    // Customer accepts a quote (funnel closure)
    // ─────────────────────────────────────────────────────────────

    public function test_customer_can_accept_a_quote_on_their_own_open_rfq(): void
    {
        $customer = $this->customer();
        $vendor = $this->vendor('catering');

        $rfq = $this->makeRfq($customer, $vendor, 'Nikkah Catering Package');
        $bid = $this->makeBid($rfq, 320000, 'PENDING');

        Livewire::actingAs($customer)
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'quotes')
            ->call('acceptQuote', $bid->id)
            ->assertHasNoErrors()
            ->assertSet('feedbackMessage', 'Quotation accepted — your service booking has been created.');

        // A ServiceBooking now exists for THIS customer with correct financials.
        $booking = ServiceBooking::query()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame($bid->id, (int) $booking->rfq_bid_id);
        $this->assertSame(320000.0, (float) $booking->quoted_amount);
        $this->assertSame('ACCEPTED', $booking->status);

        // RFQ awarded, competing PENDING bids rejected.
        $this->assertSame('AWARDED', $rfq->fresh()->status);
    }

    public function test_customer_cannot_accept_a_quote_from_another_customers_rfq(): void
    {
        $me = $this->customer();
        $intruder = $this->customer();
        $vendor = $this->vendor('catering');

        $theirRfq = $this->makeRfq($intruder, $vendor, 'Someone Elses RFQ');
        $theirBid = $this->makeBid($theirRfq, 555000, 'PENDING');

        Livewire::actingAs($me)
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'quotes')
            ->call('acceptQuote', $theirBid->id)
            ->assertStatus(404);

        // Nothing changed: no booking, RFQ still OPEN, bid still PENDING.
        $this->assertSame(0, ServiceBooking::query()->where('customer_id', $me->id)->count());
        $this->assertSame('OPEN', $theirRfq->fresh()->status);
        $this->assertSame('PENDING', $theirBid->fresh()->status);
    }

    public function test_accepting_an_already_awarded_quote_fails_gracefully(): void
    {
        $customer = $this->customer();
        $vendor = $this->vendor('catering');

        $rfq = $this->makeRfq($customer, $vendor, 'Double Award Test');
        $bidA = $this->makeBid($rfq, 100000, 'PENDING');
        $bidB = $this->makeBid($rfq, 120000, 'PENDING');

        Livewire::actingAs($customer)
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'quotes')
            ->call('acceptQuote', $bidA->id)
            ->assertHasNoErrors();

        // Second award on the same RFQ must be refused, not create a 2nd booking.
        Livewire::actingAs($customer)
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'quotes')
            ->call('acceptQuote', $bidB->id)
            ->assertSet('errorMessage', 'This quotation request has already been awarded or closed.');

        $this->assertSame(1, ServiceBooking::query()->where('customer_id', $customer->id)->count());
        $this->assertSame('REJECTED', $bidB->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────
    // Venue bookings tab regression (unchanged behavior)
    // ─────────────────────────────────────────────────────────────

    public function test_bookings_tab_still_renders_after_the_new_tab_was_added(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('My Reservations')
            ->assertSee('My Profile & Account Settings');
    }
}
