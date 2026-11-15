<?php

namespace Tests\Feature;

use App\Livewire\Auth\TwoFactorAuthComponent;
use App\Livewire\CustomerEventWorkspace;
use App\Livewire\SupportCenter;
use App\Livewire\VendorBookingsBoard;
use App\Livewire\VendorFinanceBoard;
use App\Livewire\VendorLocationsManager;
use App\Livewire\VendorPortfolioManager;
use App\Livewire\VendorQuotesBoard;
use App\Livewire\VendorServicesManager;
use App\Models\CustomerEvent;
use App\Models\CustomerEventTask;
use App\Models\CustomerEventVendor;
use App\Models\RFQ;
use App\Models\RfqBid;
use App\Models\RfqTarget;
use App\Models\ServiceBooking;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\VendorLocation;
use App\Models\VendorPayout;
use App\Models\VendorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Interactive action smoke test.
 *
 * Every button on the vendor ERP, customer workspace, support centre and 2FA
 * screens is actually *executed* — a click that throws is a crash the user
 * would hit in the browser.
 */
class InteractiveActionSmokeTest extends TestCase
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

    private function vendorOwner(Tenant $tenant): User
    {
        return User::query()->where('tenant_id', $tenant->id)
            ->where('role', 'ROLE_TENANT_OWNER')->firstOrFail();
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'ROLE_CUSTOMER']);
    }

    private function openRfq(Tenant $tenant, User $client): RFQ
    {
        return RFQ::create([
            'client_id' => $client->id,
            'tenant_id' => $tenant->id,
            'vendor_category_id' => $tenant->vendor_category_id,
            'title' => 'Smoke Test Quotation Request',
            'event_date' => now()->addDays(70)->toDateString(),
            'city' => 'Karachi',
            'guest_count' => 300,
            'budget_range' => 'PKR 1,000,000 – 2,000,000',
            'requirements' => 'A complete request used to exercise the vendor quotation board.',
            'status' => 'OPEN',
            'preferences' => [
                'booking_mode' => 'PER_HEAD',
                'start_time' => '18:00',
                'hours' => 6,
                'service_id' => null,
                'service_name' => 'Smoke Test Service',
            ],
        ]);
    }

    // ── Quotations & bids ─────────────────────────────────────────

    public function test_vendor_quotation_board_actions_run_end_to_end(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);
        $client = $this->customer();
        $rfq = $this->openRfq($vendor, $client);

        $component = Livewire::actingAs($owner)->test(VendorQuotesBoard::class);

        // Open the modal, submit a quotation.
        $component->call('openBidModal', $rfq->id)
            ->call('submitBid', $rfq->id, 1250000, 'Complete Mughlai feast with live counters and dedicated service staff.')
            ->assertHasNoErrors();

        $bid = RfqBid::query()->where('rfq_id', $rfq->id)->firstOrFail();
        $this->assertSame('PENDING', $bid->status);

        // Mark as viewed.
        $component->call('markRfqViewed', $rfq->id)->assertHasNoErrors();
        $this->assertNotNull(RfqTarget::query()->where('rfq_id', $rfq->id)->first()?->viewed_at);

        // Award the quotation → a service booking must be created.
        $component->call('awardQuote', $bid->id)->assertHasNoErrors();
        $booking = ServiceBooking::query()->where('rfq_bid_id', $bid->id)->firstOrFail();

        $this->assertSame('ACCEPTED', $booking->status);
        $this->assertSame(1250000.0, (float) $booking->quoted_amount);
        $this->assertSame('AWARDED', $rfq->fresh()->status);
        $this->assertSame('ACCEPTED', $bid->fresh()->status);
    }

    public function test_awarding_a_closed_quotation_is_rejected_gracefully(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);
        $rfq = $this->openRfq($vendor, $this->customer());

        $bid = RfqBid::create([
            'rfq_id' => $rfq->id,
            'vendor_id' => $owner->id,
            'bid_amount' => 900000,
            'proposal' => 'A valid proposal that will be rejected because the RFQ is closed.',
            'status' => 'PENDING',
        ]);

        $rfq->update(['status' => 'CLOSED']);

        Livewire::actingAs($owner)
            ->test(VendorQuotesBoard::class)
            ->call('awardQuote', $bid->id)
            ->assertSet('errorMessage', 'This quotation request has already been awarded or closed.');

        $this->assertSame(0, ServiceBooking::query()->where('rfq_bid_id', $bid->id)->count());
    }

    // ── Service bookings lifecycle ─────────────────────────────────

    public function test_vendor_service_booking_lifecycle_actions_run(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);

        $booking = ServiceBooking::create([
            'tenant_id' => $vendor->id,
            'vendor_category_id' => $vendor->vendor_category_id,
            'customer_id' => $this->customer()->id,
            'booking_reference' => 'RG-SB-SMOKE0001',
            'event_name' => 'Lifecycle smoke event',
            'event_date' => now()->addDays(45)->toDateString(),
            'quoted_amount' => 400000,
            'status' => 'ACCEPTED',
        ]);

        $component = Livewire::actingAs($owner)->test(VendorBookingsBoard::class);

        $component->call('confirmBooking', $booking->id)->assertHasNoErrors();
        $this->assertSame('CONFIRMED', $booking->fresh()->status);

        $component->call('startBooking', $booking->id)->assertHasNoErrors();
        $this->assertSame('IN_PROGRESS', $booking->fresh()->status);

        $component->call('completeBooking', $booking->id)->assertHasNoErrors();
        $this->assertSame('COMPLETED', $booking->fresh()->status);

        // Cancelling a completed booking must be refused.
        $component->call('cancelBooking', $booking->id, 'Too late')
            ->assertSet('errorMessage', 'This booking can no longer be cancelled.');
    }

    // ── Service catalogue ─────────────────────────────────────────

    public function test_service_catalogue_actions_run(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);
        $categoryId = (int) $vendor->vendor_category_id;

        Livewire::actingAs($owner)
            ->test(VendorServicesManager::class)
            ->set('serviceName', 'Smoke Test Package')
            ->set('serviceDescription', 'Created by the interactive action smoke test.')
            ->set('pricingType', 'FIXED')
            ->set('priceFrom', 150000)
            ->set('serviceUnit', 'per event')
            ->set('vendorCategoryId', $categoryId)
            ->call('saveService')
            ->assertHasNoErrors();

        $service = VendorService::query()
            ->where('tenant_id', $vendor->id)
            ->where('name', 'Smoke Test Package')
            ->firstOrFail();

        $component = Livewire::actingAs($owner)->test(VendorServicesManager::class);
        $component->call('toggleService', $service->id)->assertHasNoErrors();

        $this->assertFalse((bool) $service->fresh()->is_active);
    }

    // ── Portfolio ─────────────────────────────────────────────────

    public function test_portfolio_manager_actions_run(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);

        $component = Livewire::actingAs($owner)->test(VendorPortfolioManager::class);
        $component->call('refreshMediaUrls')->assertHasNoErrors();

        $this->assertIsArray($component->get('media'));
    }

    // ── Locations ─────────────────────────────────────────────────

    public function test_locations_manager_actions_run(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);

        $component = Livewire::actingAs($owner)->test(VendorLocationsManager::class)
            ->set('locationName', 'Smoke Test Branch')
            ->set('branchType', 'BRANCH')
            ->set('city', 'Karachi')
            ->call('saveLocation')
            ->assertHasNoErrors();

        $location = VendorLocation::query()
            ->where('tenant_id', $vendor->id)
            ->where('name', 'Smoke Test Branch')
            ->firstOrFail();

        Livewire::actingAs($owner)
            ->test(VendorLocationsManager::class)
            ->call('openEditModal', $location->id)
            ->assertSet('locationName', 'Smoke Test Branch')
            ->call('toggleActive', $location->id)
            ->assertHasNoErrors();

        $this->assertFalse((bool) $location->fresh()->is_active);
    }

    // ── Finance ───────────────────────────────────────────────────

    public function test_finance_board_payout_request_runs_and_is_bounded(): void
    {
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);

        $component = Livewire::actingAs($owner)->test(VendorFinanceBoard::class);

        // Without a wallet balance a payout must be refused.
        $component->call('requestPayout')
            ->assertHasErrors(['payoutAmount']);

        $this->assertSame(0, VendorPayout::query()->count());
    }

    // ── Customer workspace ─────────────────────────────────────────

    public function test_customer_workspace_actions_run(): void
    {
        $customer = $this->customer();
        $vendor = $this->cateringVendor();

        $component = Livewire::actingAs($customer)->test(CustomerEventWorkspace::class)
            ->set('eventName', 'Smoke Test Event')
            ->set('eventType', 'WALIMA')
            ->set('eventDate', now()->addDays(80)->toDateString())
            ->set('eventCity', 'Lahore')
            ->call('saveEvent')
            ->assertHasNoErrors();

        $event = CustomerEvent::query()->where('user_id', $customer->id)->firstOrFail();

        $component->set('taskTitle', 'Book the caterer')->call('addTask')->assertHasNoErrors();

        $task = CustomerEventTask::query()->where('customer_event_id', $event->id)->firstOrFail();

        $component->call('toggleTask', $task->id);
        $this->assertSame('DONE', $task->fresh()->status);

        // Shortlist a marketplace vendor.
        $component->call('attachVendor', $vendor->id)->assertHasNoErrors();

        $row = CustomerEventVendor::query()
            ->where('customer_event_id', $event->id)
            ->where('tenant_id', $vendor->id)
            ->firstOrFail();

        $component->call('updateVendorStatus', $row->id, 'REQUESTED');
        $this->assertSame('REQUESTED', $row->fresh()->status);

        $component->call('detachVendor', $row->id)->assertHasNoErrors();
        $this->assertSame(0, CustomerEventVendor::query()->where('customer_event_id', $event->id)->count());

        // Remove the event itself.
        $component->call('deleteEvent', $event->id)->assertHasNoErrors();
        $this->assertNull(CustomerEvent::query()->where('id', $event->id)->first());
    }

    // ── Support centre ────────────────────────────────────────────

    public function test_support_centre_actions_run(): void
    {
        $customer = $this->customer();
        $vendor = $this->cateringVendor();
        $owner = $this->vendorOwner($vendor);

        $component = Livewire::actingAs($customer)->test(SupportCenter::class)
            ->set('subject', 'Smoke test ticket')
            ->set('category', 'BOOKING')
            ->set('priority', 'HIGH')
            ->set('body', 'This ticket is created by the interactive action smoke test.')
            ->call('createTicket')
            ->assertHasNoErrors();

        $ticket = SupportTicket::query()->where('user_id', $customer->id)->firstOrFail();

        // The customer replies.
        $component->set('replyBody', 'Adding a little more detail to my ticket.')->call('reply')->assertHasNoErrors();

        // The vendor replies on the ticket raised against their business.
        $vendorComponent = Livewire::actingAs($owner)->test(SupportCenter::class);
        $vendorComponent->call('openTicket', $ticket->id);
        $vendorComponent->set('replyBody', 'Thank you for reaching out — we are on it.')
            ->call('reply')
            ->assertHasNoErrors();

        $this->assertSame('IN_PROGRESS', $ticket->fresh()->status);
        // Initial ticket body + customer reply + vendor reply.
        $this->assertSame(3, TicketMessage::query()->where('support_ticket_id', $ticket->id)->count());
    }

    // ── Two-factor authentication ──────────────────────────────────

    public function test_two_factor_setup_verification_and_disable_run(): void
    {
        $owner = $this->vendorOwner($this->cateringVendor());

        $component = Livewire::actingAs($owner)->test(TwoFactorAuthComponent::class);

        // The setup screen must expose a secret and a QR data URI.
        $this->assertNotSame('', $component->get('secret'));
        $this->assertNotSame('', $component->get('qrCodeDataUri'));
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $component->get('qrCodeDataUri'));

        // A wrong code must be refused.
        $component->set('code', '000000')->call('enableTwoFactor')->assertSet('errorMessage', 'Invalid verification code. Please try again.');

        // The real code must enable it and persist the encrypted secret.
        $google2fa = new Google2FA;
        $valid = $google2fa->getCurrentOtp($component->get('secret'));

        $component->set('code', $valid)->call('enableTwoFactor')->assertHasNoErrors();

        $owner->refresh();
        $this->assertTrue((bool) $owner->two_factor_enabled);
        $this->assertNotNull($owner->two_factor_secret);
        $this->assertCount(8, $owner->two_factor_recovery_codes);

        // The stored secret must not be readable in plain text.
        $raw = DB::table('users')->where('id', $owner->id)->value('two_factor_secret');
        $this->assertNotSame($owner->two_factor_secret, $raw);
        $this->assertStringNotContainsString($component->get('secret'), (string) $raw);

        // Disabling requires the password.
        $component->set('password', 'wrong-password')->call('disableTwoFactor')->assertSet('errorMessage', 'Your password is incorrect.');
        $this->assertTrue((bool) $owner->fresh()->two_factor_enabled);

        $component->set('password', 'password')->call('disableTwoFactor')->assertHasNoErrors();
        $this->assertFalse((bool) $owner->fresh()->two_factor_enabled);
    }

    public function test_two_factor_recovery_code_is_single_use(): void
    {
        $owner = $this->vendorOwner($this->cateringVendor());

        $component = Livewire::actingAs($owner)->test(TwoFactorAuthComponent::class);
        $recoveryPlain = $component->get('recoveryCodes')[0];

        $google2fa = new Google2FA;
        $component->set('code', $google2fa->getCurrentOtp($component->get('secret')))->call('enableTwoFactor');

        $owner->refresh();
        $this->assertTrue((bool) $owner->two_factor_enabled);

        // The recovery code verifies…
        $verifier = Livewire::actingAs($owner)->test(TwoFactorAuthComponent::class);
        $verifier->set('code', $recoveryPlain)->call('verifyTwoFactor');

        // …and is consumed, so it cannot be replayed.
        $this->assertCount(7, $owner->fresh()->two_factor_recovery_codes);
    }
}
