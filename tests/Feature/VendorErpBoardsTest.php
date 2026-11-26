<?php

namespace Tests\Feature;

use App\Livewire\VendorQuotesBoard;
use App\Livewire\VendorServicesManager;
use App\Models\RFQ;
use App\Models\RfqBid;
use App\Models\RfqTarget;
use App\Models\ServiceBooking;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Vendor ERP boards coverage (Phase 2/3): quotations & bids inbox,
 * service bookings board and service catalog manager.
 */
class VendorErpBoardsTest extends TestCase
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

    private function openRfq(Tenant $tenant, User $client, ?int $vendorCategoryId = null, string $title = 'Royal Event Quotation Request'): RFQ
    {
        return RFQ::create([
            'client_id' => $client->id,
            'tenant_id' => $tenant->id,
            'vendor_category_id' => $vendorCategoryId ?? $tenant->vendor_category_id,
            'title' => $title,
            'event_date' => now()->addDays(75)->toDateString(),
            'city' => 'Karachi',
            'guest_count' => 350,
            'budget_range' => 'PKR 2,000,000 – 4,000,000',
            'requirements' => 'Full Mughlai feast with live stations, service staff and tasting session.',
            'status' => 'OPEN',
            'preferences' => [
                'booking_mode' => 'PER_HEAD',
                'start_time' => '18:00',
                'hours' => 8,
                'service_id' => null,
                'service_name' => 'Royal Walima Feast',
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Vendor Quotations & Bids board
    // ─────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_vendor_quotes_board(): void
    {
        $this->get('/vendor/quotes')->assertRedirect(route('login'));
    }

    public function test_vendor_owner_can_render_quotes_board(): void
    {
        $this->actingAs($this->vendorOwner($this->cateringVendor()))
            ->get('/vendor/quotes')
            ->assertOk();
    }

    public function test_customer_is_redirected_away_from_vendor_quotes_board(): void
    {
        $this->actingAs($this->customer())
            ->get('/vendor/quotes')
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_vendor_can_submit_bid_for_open_rfq(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);
        $rfq = $this->openRfq($vendor, $this->customer());

        Livewire::actingAs($owner)
            ->test(VendorQuotesBoard::class)
            ->call('submitBid', $rfq->id, 2450000, 'Royal mutton dum pukht feast with live BBQ counter, continental counters and dedicated service staff. Full menu tasting session arranged before the event.')
            ->assertHasNoErrors();

        $bid = RfqBid::query()
            ->where('rfq_id', $rfq->id)
            ->where('vendor_id', $owner->id)
            ->firstOrFail();

        $this->assertSame('PENDING', $bid->status);
        $this->assertSame(2450000.0, (float) $bid->bid_amount);

        Livewire::actingAs($owner)
            ->test(VendorQuotesBoard::class)
            ->call('declineRfq', $rfq->id)
            ->assertHasNoErrors();

        $this->assertSame('CLOSED', $rfq->fresh()->status);
    }

    public function test_bid_submission_requires_positive_amount_and_detailed_proposal(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);
        $rfq = $this->openRfq($vendor, $this->customer());

        Livewire::actingAs($owner)
            ->test(VendorQuotesBoard::class)
            ->call('submitBid', $rfq->id, 0, 'A sufficiently detailed and competitive proposal for the client to review.')
            ->assertHasErrors(['bidAmount']);

        Livewire::actingAs($owner)
            ->test(VendorQuotesBoard::class)
            ->call('submitBid', $rfq->id, 1850000, 'Short proposal.')
            ->assertHasErrors(['bidProposal']);

        $this->assertSame(0, RfqBid::query()->where('rfq_id', $rfq->id)->count());
    }

    public function test_vendor_can_mark_open_rfq_as_viewed(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);
        $rfq = $this->openRfq($vendor, $this->customer());

        RfqTarget::create([
            'rfq_id' => $rfq->id,
            'tenant_id' => $vendor->id,
        ]);

        Livewire::actingAs($owner)
            ->test(VendorQuotesBoard::class)
            ->call('markRfqViewed', $rfq->id)
            ->assertHasNoErrors();

        $target = RfqTarget::query()
            ->where('rfq_id', $rfq->id)
            ->where('tenant_id', $vendor->id)
            ->firstOrFail();

        $this->assertNotNull($target->viewed_at);
    }

    public function test_vendor_board_is_scoped_to_own_tenant_rfqs(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);
        $photography = $this->photographyVendor();
        $photographyOwner = $this->vendorOwner($photography);

        $ownRfq = $this->openRfq($vendor, $this->customer(), (int) $vendor->vendor_category_id, 'Catering Royal Quotation Request');
        $foreignRfq = $this->openRfq($photography, $this->customer(), (int) $photography->vendor_category_id, 'Photography Royal Quotation Request');

        $this->actingAs($owner)
            ->get('/vendor/quotes')
            ->assertOk()
            ->assertSee($ownRfq->title)
            ->assertDontSee($foreignRfq->title);

        // The photography vendor owner must not see the catering tenant's RFQ.
        $this->actingAs($photographyOwner)
            ->get('/vendor/quotes')
            ->assertOk()
            ->assertSee($foreignRfq->title)
            ->assertDontSee($ownRfq->title);
    }

    // ─────────────────────────────────────────────────────────────
    // Vendor Service Catalog manager
    // ─────────────────────────────────────────────────────────────

    public function test_vendor_owner_can_render_service_catalog(): void
    {
        $this->actingAs($this->vendorOwner($this->cateringVendor()))
            ->get('/vendor/services')
            ->assertOk();
    }

    public function test_vendor_owner_can_create_service_in_catalog(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);
        $categoryId = (int) $vendor->vendor_category_id;

        Livewire::actingAs($owner)
            ->test(VendorServicesManager::class)
            ->set('serviceName', 'Royal Biryani Buffet Deluxe')
            ->set('serviceDescription', 'Layered biryani buffet with raita, salan and dedicated service staff.')
            ->set('pricingType', 'PER_HEAD')
            ->set('priceFrom', 2400)
            ->set('priceTo', 3600)
            ->set('serviceUnit', 'per head')
            ->set('vendorCategoryId', $categoryId)
            ->call('saveService')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vendor_services', [
            'tenant_id' => $vendor->id,
            'vendor_category_id' => $categoryId,
            'name' => 'Royal Biryani Buffet Deluxe',
            'pricing_type' => 'PER_HEAD',
            'price_from' => 2400,
            'price_to' => 3600,
            'unit' => 'per head',
        ]);
    }

    public function test_service_creation_requires_service_name(): void
    {
        Livewire::actingAs($this->vendorOwner($this->cateringVendor()))
            ->test(VendorServicesManager::class)
            ->set('serviceName', '')
            ->call('saveService')
            ->assertHasErrors(['serviceName']);
    }

    public function test_per_head_and_hourly_services_require_a_positive_base_price(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);

        Livewire::actingAs($owner)
            ->test(VendorServicesManager::class)
            ->set('serviceName', 'Unpriced Per Head Service')
            ->set('pricingType', 'PER_HEAD')
            ->set('priceFrom', 0)
            ->set('priceTo', 1000)
            ->call('saveService')
            ->assertHasErrors(['priceFrom']);

        Livewire::actingAs($owner)
            ->test(VendorServicesManager::class)
            ->set('serviceName', 'Unpriced Hourly Service')
            ->set('pricingType', 'HOURLY')
            ->set('priceFrom', null)
            ->set('priceTo', null)
            ->call('saveService')
            ->assertHasErrors(['priceFrom']);

        foreach (['PER_HEAD', 'HOURLY'] as $pricingType) {
            Livewire::actingAs($owner)
                ->test(VendorServicesManager::class)
                ->set('serviceName', 'Missing Base Price '.$pricingType)
                ->set('pricingType', $pricingType)
                ->set('priceFrom', null)
                ->call('saveService')
                ->assertHasErrors(['priceFrom']);
        }

        $this->assertDatabaseMissing('vendor_services', ['tenant_id' => $vendor->id, 'name' => 'Unpriced Per Head Service']);
        $this->assertDatabaseMissing('vendor_services', ['tenant_id' => $vendor->id, 'name' => 'Unpriced Hourly Service']);
        $this->assertDatabaseMissing('vendor_services', ['tenant_id' => $vendor->id, 'name' => 'Missing Base Price PER_HEAD']);
        $this->assertDatabaseMissing('vendor_services', ['tenant_id' => $vendor->id, 'name' => 'Missing Base Price HOURLY']);
    }

    public function test_service_prices_above_decimal_column_limit_are_rejected(): void
    {
        $owner = $this->createVendorOwner();

        Livewire::actingAs($owner)
            ->test(VendorServicesManager::class)
            ->set('serviceName', 'Overflow Service')
            ->set('pricingType', 'FIXED')
            ->set('priceFrom', 10_000_000_000.00)
            ->call('saveService')
            ->assertHasErrors(['priceFrom']);

        $this->assertDatabaseMissing('vendor_services', [
            'tenant_id' => $owner->tenant_id,
            'name' => 'Overflow Service',
        ]);
    }

    public function test_non_finite_service_prices_are_rejected_before_persistence(): void
    {
        $owner = $this->createVendorOwner();

        Livewire::actingAs($owner)
            ->test(VendorServicesManager::class)
            ->set('serviceName', 'Infinite Service')
            ->set('pricingType', 'FIXED')
            ->set('priceFrom', INF)
            ->call('saveService')
            ->assertHasErrors(['priceFrom']);

        Livewire::actingAs($owner)
            ->test(VendorServicesManager::class)
            ->set('serviceName', 'Not-a-number Service')
            ->set('pricingType', 'FIXED')
            ->set('priceTo', NAN)
            ->call('saveService')
            ->assertHasErrors(['priceTo']);

        $this->assertDatabaseMissing('vendor_services', [
            'tenant_id' => $owner->tenant_id,
            'name' => 'Infinite Service',
        ]);
        $this->assertDatabaseMissing('vendor_services', [
            'tenant_id' => $owner->tenant_id,
            'name' => 'Not-a-number Service',
        ]);
    }

    public function test_service_price_ranges_and_negative_values_are_rejected(): void
    {
        $owner = $this->vendorOwner($this->cateringVendor());

        Livewire::actingAs($owner)
            ->test(VendorServicesManager::class)
            ->set('serviceName', 'Invalid Price Range Service')
            ->set('pricingType', 'FIXED')
            ->set('priceFrom', 5000)
            ->set('priceTo', 4000)
            ->call('saveService')
            ->assertHasErrors(['priceTo']);

        Livewire::actingAs($owner)
            ->test(VendorServicesManager::class)
            ->set('serviceName', 'Negative Price Service')
            ->set('pricingType', 'FIXED')
            ->set('priceFrom', -100)
            ->set('priceTo', null)
            ->call('saveService')
            ->assertHasErrors(['priceFrom']);

        $this->assertDatabaseMissing('vendor_services', ['name' => 'Invalid Price Range Service']);
        $this->assertDatabaseMissing('vendor_services', ['name' => 'Negative Price Service']);
    }

    // ─────────────────────────────────────────────────────────────
    // Vendor Service Bookings board
    // ─────────────────────────────────────────────────────────────

    public function test_vendor_owner_can_render_service_bookings_board(): void
    {
        $this->actingAs($this->vendorOwner($this->cateringVendor()))
            ->get('/vendor/bookings')
            ->assertOk();
    }

    public function test_service_bookings_board_is_scoped_to_requester_tenant(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);
        $photography = $this->photographyVendor();
        $photographyOwner = $this->vendorOwner($photography);

        $customer = $this->customer();

        $cateringBooking = ServiceBooking::create([
            'tenant_id' => $vendor->id,
            'vendor_category_id' => $vendor->vendor_category_id,
            'customer_id' => $customer->id,
            'booking_reference' => 'RG-SB-9F3K2Q1',
            'event_name' => 'Royal Walima Dinner',
            'event_date' => now()->addDays(60)->toDateString(),
            'quoted_amount' => 2450000,
            'status' => 'ACCEPTED',
        ]);

        $photographyBooking = ServiceBooking::create([
            'tenant_id' => $photography->id,
            'vendor_category_id' => $photography->vendor_category_id,
            'customer_id' => $customer->id,
            'booking_reference' => 'RG-SB-B2N8M4X',
            'event_name' => 'Baraat Cinematic Shoot',
            'event_date' => now()->addDays(45)->toDateString(),
            'quoted_amount' => 95000,
            'status' => 'CONFIRMED',
        ]);

        $this->actingAs($owner)
            ->get('/vendor/bookings')
            ->assertOk()
            ->assertSee('Royal Walima Dinner')
            ->assertDontSee('Baraat Cinematic Shoot');

        $this->actingAs($photographyOwner)
            ->get('/vendor/bookings')
            ->assertOk()
            ->assertSee('Baraat Cinematic Shoot')
            ->assertDontSee('Royal Walima Dinner');
    }

    public function test_service_pricing_rejects_negative_amounts(): void
    {
        $vendor = $this->cateringVendor();

        Livewire::actingAs($this->vendorOwner($vendor))
            ->test(VendorServicesManager::class)
            ->set('serviceName', 'Invalid Negative Price')
            ->set('pricingType', 'FIXED')
            ->set('priceFrom', -1)
            ->call('saveService')
            ->assertHasErrors(['priceFrom']);

        $this->assertDatabaseMissing('vendor_services', [
            'tenant_id' => $vendor->id,
            'name' => 'Invalid Negative Price',
        ]);
    }

    public function test_service_pricing_rejects_end_price_below_start_price(): void
    {
        $vendor = $this->cateringVendor();

        Livewire::actingAs($this->vendorOwner($vendor))
            ->test(VendorServicesManager::class)
            ->set('serviceName', 'Invalid Price Range')
            ->set('pricingType', 'FIXED')
            ->set('priceFrom', 5000)
            ->set('priceTo', 4999)
            ->call('saveService')
            ->assertHasErrors(['priceTo']);

        $this->assertDatabaseMissing('vendor_services', [
            'tenant_id' => $vendor->id,
            'name' => 'Invalid Price Range',
        ]);
    }

    public function test_vendor_quotes_board_never_exposes_competitor_bids_or_prefills_them(): void
    {
        $caterer = $this->cateringVendor();
        $photographer = $this->photographyVendor();
        $customer = $this->customer();
        $catererOwner = $this->vendorOwner($caterer);
        $photographerOwner = $this->vendorOwner($photographer);
        $rfq = $this->openRfq($caterer, $customer);

        RfqTarget::create([
            'rfq_id' => $rfq->id,
            'tenant_id' => $photographer->id,
        ]);

        RfqBid::create([
            'rfq_id' => $rfq->id,
            'vendor_id' => $catererOwner->id,
            'bid_amount' => 2345678,
            'proposal' => 'Confidential competitor quotation that must not be visible.',
            'status' => 'PENDING',
        ]);

        $component = Livewire::actingAs($photographerOwner)
            ->test(VendorQuotesBoard::class)
            ->assertSee($rfq->title)
            ->assertDontSee('2,345,678')
            ->assertDontSee($catererOwner->name);

        $component->call('openBidModal', $rfq->id)
            ->assertSet('bidAmount', 0.0)
            ->assertSet('bidProposal', '');
    }


    public function test_vendor_cannot_submit_or_overwrite_bid_after_rfq_is_closed(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);
        $rfq = $this->openRfq($vendor, $this->customer());
        $rfq->update(['status' => 'CLOSED']);

        Livewire::actingAs($owner)
            ->test(VendorQuotesBoard::class)
            ->call('submitBid', $rfq->id, 1250000, 'A detailed quotation that must not be accepted after closure.')
            ->assertSet('errorMessage', 'This quotation is no longer open for offers.');

        $this->assertSame(0, RfqBid::query()->where('rfq_id', $rfq->id)->count());
    }

}
