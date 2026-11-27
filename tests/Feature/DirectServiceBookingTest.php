<?php

namespace Tests\Feature;

use App\Exceptions\ServiceBookingException;
use App\Livewire\CustomerDashboard;
use App\Livewire\DirectBooking;
use App\Livewire\VendorBookingsBoard;
use App\Models\ServiceBooking;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorPackage;
use App\Models\VendorService;
use App\Services\ServiceBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 4 — universal service & booking engine (direct "Book Now").
 *
 * Before this phase the ONLY way to create a ServiceBooking was winning an
 * RFQ round-trip: even fixed-price packages linked to "Request Quote". This
 * suite covers the new direct path: server-side price resolution, booking
 * mode driven validation, slot exclusivity (APPOINTMENT / HOURLY), IDOR and
 * hidden-vendor guards, and the hand-off into the existing vendor board
 * lifecycle (ACCEPTED → CONFIRMED).
 */
class DirectServiceBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function vendor(string $slug): Tenant
    {
        return Tenant::query()->where('slug', $slug)->firstOrFail();
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'ROLE_CUSTOMER']);
    }

    private function vendorOwner(Tenant $tenant): User
    {
        return User::query()->where('tenant_id', $tenant->id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();
    }

    private function makeService(Tenant $tenant, array $overrides = []): VendorService
    {
        return VendorService::create(array_merge([
            'tenant_id' => $tenant->id,
            'vendor_category_id' => $tenant->vendor_category_id,
            'name' => 'Signature Service',
            'description' => 'A complete, vendor-managed service.',
            'pricing_type' => 'FIXED',
            'price_from' => 1500,
            'is_active' => true,
            'sort' => 1,
        ], $overrides));
    }

    private function makePackage(Tenant $tenant, array $overrides = []): VendorPackage
    {
        return VendorPackage::create(array_merge([
            'tenant_id' => $tenant->id,
            'vendor_category_id' => $tenant->vendor_category_id,
            'name' => 'Royal Package',
            'description' => 'Everything included.',
            'items' => ['Setup', 'Coverage', 'Delivery'],
            'pricing_type' => 'FIXED',
            'price' => 75000,
            'is_active' => true,
            'sort' => 1,
        ], $overrides));
    }

    private function bookingPage(Tenant $tenant, User $actor, array $params = [])
    {
        return Livewire::actingAs($actor)->test(
            DirectBooking::class,
            array_merge(['slug' => $tenant->slug], $params)
        );
    }

    private function baseForm(): array
    {
        return [
            'eventName' => 'Ali & Fatima Walima',
            'eventDate' => now()->addDays(45)->toDateString(),
            'requirements' => 'Please arrive 30 minutes early.',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Access control
    // ─────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login_from_the_booking_page(): void
    {
        $vendor = $this->vendor('nawab-caterers');

        $this->get(route('public.vendor-book', ['slug' => $vendor->slug]))
            ->assertRedirect(route('login'));
    }

    public function test_hidden_vendor_returns_404(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $vendor->update(['marketplace_visible' => false]);

        $this->actingAs($this->customer())
            ->get(route('public.vendor-book', ['slug' => $vendor->slug]))
            ->assertNotFound();
    }

    public function test_unapproved_vendor_returns_404(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $vendor->update(['verification_status' => 'PENDING']);

        $this->actingAs($this->customer())
            ->get(route('public.vendor-book', ['slug' => $vendor->slug]))
            ->assertNotFound();
    }

    public function test_expired_vendor_cannot_receive_direct_service_bookings(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $customer = $this->customer();
        $service = $this->makeService($vendor);

        $vendor->update([
            'subscription_status' => 'EXPIRED',
            'subscription_expires_at' => now()->addYear(),
        ]);

        try {
            app(ServiceBookingService::class)->createDirectBooking(
                $vendor,
                (int) $customer->id,
                $service,
                [
                    'event_name' => 'Expired Vendor Test Event',
                    'event_date' => now()->addDays(60)->toDateString(),
                    'guest_count' => 200,
                ]
            );
            $this->fail('An expired vendor must not receive a new service booking.');
        } catch (ServiceBookingException $exception) {
            $this->assertSame(409, $exception->getCode());
        }

        $this->assertDatabaseMissing('service_bookings', [
            'tenant_id' => $vendor->id,
            'event_name' => 'Expired Vendor Test Event',
        ]);
    }

    public function test_hidden_vendor_cannot_receive_booking_from_a_stale_page(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $customer = $this->customer();
        $service = $this->makeService($vendor);
        $vendor->update(['marketplace_visible' => false]);

        try {
            app(ServiceBookingService::class)->createDirectBooking(
                $vendor,
                (int) $customer->id,
                $service,
                [
                    'event_name' => 'Hidden Vendor Stale Page',
                    'event_date' => now()->addDays(60)->toDateString(),
                    'guest_count' => 200,
                ]
            );
            $this->fail('A vendor hidden after page load must not receive a new booking.');
        } catch (ServiceBookingException $exception) {
            $this->assertSame(409, $exception->getCode());
        }

        $this->assertDatabaseMissing('service_bookings', [
            'tenant_id' => $vendor->id,
            'event_name' => 'Hidden Vendor Stale Page',
        ]);
    }

    public function test_booking_service_rejects_a_bookable_owned_by_another_vendor(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $otherVendor = $this->vendor('lens-and-lantern-studio');
        $customer = $this->customer();
        $foreignService = $this->makeService($otherVendor);

        try {
            app(ServiceBookingService::class)->createDirectBooking(
                $vendor,
                (int) $customer->id,
                $foreignService,
                [
                    'event_name' => 'Cross Tenant Service Attempt',
                    'event_date' => now()->addDays(60)->toDateString(),
                    'guest_count' => 200,
                ]
            );
            $this->fail('A vendor must not book another tenant\'s service.');
        } catch (ServiceBookingException $exception) {
            $this->assertSame(404, $exception->getCode());
            $this->assertStringContainsString('no longer available for this vendor', $exception->getMessage());
        }

        $this->assertDatabaseMissing('service_bookings', [
            'tenant_id' => $vendor->id,
            'event_name' => 'Cross Tenant Service Attempt',
        ]);
    }

    public function test_booking_service_rejects_a_stale_inactive_bookable_model(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $customer = $this->customer();
        $service = $this->makeService($vendor);
        VendorService::query()->whereKey($service->id)->update(['is_active' => false]);

        try {
            app(ServiceBookingService::class)->createDirectBooking(
                $vendor,
                (int) $customer->id,
                $service,
                [
                    'event_name' => 'Inactive Service Attempt',
                    'event_date' => now()->addDays(60)->toDateString(),
                    'guest_count' => 200,
                ]
            );
            $this->fail('An inactive service must not be bookable through a stale model.');
        } catch (ServiceBookingException $exception) {
            $this->assertSame(404, $exception->getCode());
            $this->assertStringContainsString('no longer available for this vendor', $exception->getMessage());
        }

        $this->assertDatabaseMissing('service_bookings', [
            'tenant_id' => $vendor->id,
            'event_name' => 'Inactive Service Attempt',
        ]);
    }

    public function test_opening_the_page_without_an_item_asks_you_to_pick_one(): void
    {
        $vendor = $this->vendor('nawab-caterers');

        $this->bookingPage($vendor, $this->customer())
            ->assertSee('Choose a service or package first');
    }

    // ─────────────────────────────────────────────────────────────
    // Server-side price resolution (PER_HEAD / packages)
    // ─────────────────────────────────────────────────────────────

    public function test_per_head_service_books_with_a_server_computed_price(): void
    {
        $vendor = $this->vendor('nawab-caterers'); // catering → PER_HEAD
        $service = $this->makeService($vendor, ['name' => 'Mughlai Feast', 'price_from' => 1850]);
        $customer = $this->customer();

        $this->bookingPage($vendor, $customer, ['service' => $service->id])
            ->set('eventName', 'Walima Feast')
            ->set('eventDate', now()->addDays(30)->toDateString())
            ->set('guestCount', 320)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $booking = ServiceBooking::query()->where('customer_id', $customer->id)->firstOrFail();

        // 1,850 × 320 guests — resolved on the server, never from the form.
        $this->assertSame(592000.0, (float) $booking->quoted_amount);
        $this->assertSame('ACCEPTED', $booking->status);
        $this->assertSame(320, (int) $booking->guest_count);
        $this->assertSame($service->id, $booking->bookable_id);
        $this->assertSame(VendorService::class, $booking->bookable_type);
        $this->assertSame($vendor->id, $booking->tenant_id);
        $this->assertSame('Walima Feast', $booking->event_name);
        $this->assertSame('direct', $booking->requirements_json['source'] ?? null);

        // Financial pipeline runs exactly like an awarded quote.
        $financials = app(ServiceBookingService::class)
            ->calculateFinancials(592000.0, 0.0, $vendor);

        $this->assertSame($financials['commission_amount'], (float) $booking->commission_amount);
        $this->assertSame($financials['net_vendor_amount'], (float) $booking->net_vendor_amount);
        $this->assertSame($financials['advance_amount'], (float) $booking->advance_amount);
        $this->assertGreaterThan(0, (float) $booking->commission_amount);
        $this->assertStringStartsWith('RG-', $booking->booking_reference);
    }

    public function test_extra_money_keys_in_the_attributes_are_ignored(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $service = $this->makeService($vendor, ['price_from' => 2000]);
        $customer = $this->customer();

        // A caller (or a tampered payload) tries to dictate the money fields.
        $booking = app(ServiceBookingService::class)->createDirectBooking(
            $vendor,
            $customer->id,
            $service,
            [
                'event_name' => 'Tamper attempt',
                'event_date' => now()->addDays(20)->toDateString(),
                'guest_count' => 100,
                'quoted_amount' => 1.0,
                'advance_amount' => 0.0,
                'commission_amount' => 0.0,
                'net_vendor_amount' => 1.0,
                'status' => 'COMPLETED',
                'paid_amount' => 999999.0,
            ]
        );

        $this->assertSame(200000.0, (float) $booking->quoted_amount); // 2,000 × 100
        $this->assertSame('ACCEPTED', $booking->status);
        $this->assertSame(0.0, (float) $booking->paid_amount);
        $this->assertGreaterThan(0, (float) $booking->commission_amount);
    }

    public function test_package_books_at_its_flat_price(): void
    {
        $vendor = $this->vendor('gulshan-decor-house'); // decoration → PROJECT
        $package = $this->makePackage($vendor, ['price' => 120000]);
        $customer = $this->customer();

        $this->bookingPage($vendor, $customer, ['package' => $package->id])
            ->set('eventName', 'Stage Décor')
            ->set('eventDate', now()->addDays(25)->toDateString())
            ->set('guestCount', 500)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $booking = ServiceBooking::query()->where('customer_id', $customer->id)->firstOrFail();

        // Packages are flat-priced — the guest count must NOT inflate it.
        $this->assertSame(120000.0, (float) $booking->quoted_amount);
        $this->assertSame($package->id, $booking->bookable_id);
        $this->assertSame(VendorPackage::class, $booking->bookable_type);
    }

    public function test_per_head_booking_requires_a_guest_count(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $service = $this->makeService($vendor, ['price_from' => 1850]);
        $customer = $this->customer();

        $this->bookingPage($vendor, $customer, ['service' => $service->id])
            ->set('eventName', 'No guests given')
            ->set('eventDate', now()->addDays(30)->toDateString())
            ->call('save')
            ->assertHasErrors(['guestCount']);

        $this->assertSame(0, ServiceBooking::query()->count());
    }

    public function test_past_event_date_is_rejected(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $service = $this->makeService($vendor);
        $customer = $this->customer();

        $this->bookingPage($vendor, $customer, ['service' => $service->id])
            ->set('eventName', 'Yesterday')
            ->set('eventDate', now()->subDay()->toDateString())
            ->set('guestCount', 100)
            ->call('save')
            ->assertHasErrors(['eventDate']);

        $this->assertSame(0, ServiceBooking::query()->count());
    }

    // ─────────────────────────────────────────────────────────────
    // Quote-only items (no fixed price) stay quote-only
    // ─────────────────────────────────────────────────────────────

    public function test_item_without_a_fixed_price_is_quote_only(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $service = $this->makeService($vendor, ['price_from' => null, 'price_to' => null]);
        $customer = $this->customer();

        $page = $this->bookingPage($vendor, $customer, ['service' => $service->id]);

        $page->assertSet('canDirectBook', false)
            ->assertSee('Price on request')
            ->assertSee(route('public.vendor-quote', ['slug' => $vendor->slug]), false);

        $page->set('eventName', 'Should not book')
            ->set('eventDate', now()->addDays(30)->toDateString())
            ->set('guestCount', 100)
            ->call('save')
            ->assertSet('errorMessage', 'This item does not have a fixed price yet — please request a quotation instead.');

        $this->assertSame(0, ServiceBooking::query()->count());
    }

    // ─────────────────────────────────────────────────────────────
    // Slot exclusivity
    // ─────────────────────────────────────────────────────────────

    public function test_appointment_slot_cannot_be_double_booked(): void
    {
        $vendor = $this->vendor('noor-bridal-lounge'); // beauty → APPOINTMENT
        $service = $this->makeService($vendor, ['name' => 'Bridal Makeup', 'price_from' => 45000]);
        $date = now()->addDays(20)->toDateString();

        // An existing booking already owns the 11:00 slot.
        ServiceBooking::create([
            'tenant_id' => $vendor->id,
            'vendor_category_id' => $vendor->vendor_category_id,
            'customer_id' => $this->customer()->id,
            'booking_reference' => 'RG-SB-EXIST1',
            'event_name' => 'Existing appointment',
            'event_date' => $date,
            'start_time' => '11:00',
            'quoted_amount' => 45000,
            'status' => 'CONFIRMED',
        ]);

        $customer = $this->customer();

        $this->bookingPage($vendor, $customer, ['service' => $service->id])
            ->set('eventName', 'Bridal trial')
            ->set('eventDate', $date)
            ->set('startTime', '11:00')
            ->call('save')
            ->assertSet('errorMessage', 'That time slot is already booked. Please pick another time.');

        // Exactly one booking still exists — the rejected attempt persisted nothing.
        $this->assertSame(1, ServiceBooking::query()->where('tenant_id', $vendor->id)->count());
        $this->assertSame(0, ServiceBooking::query()->where('customer_id', $customer->id)->count());
    }

    public function test_appointment_slot_frees_up_after_cancellation(): void
    {
        $vendor = $this->vendor('noor-bridal-lounge');
        $service = $this->makeService($vendor, ['price_from' => 45000]);
        $date = now()->addDays(20)->toDateString();

        $existing = ServiceBooking::create([
            'tenant_id' => $vendor->id,
            'vendor_category_id' => $vendor->vendor_category_id,
            'customer_id' => $this->customer()->id,
            'booking_reference' => 'RG-SB-CANCEL1',
            'event_name' => 'Cancelled appointment',
            'event_date' => $date,
            'start_time' => '11:00',
            'quoted_amount' => 45000,
            'status' => 'CONFIRMED',
        ]);

        // Cancelled bookings must not keep holding the slot.
        $existing->update(['status' => 'CANCELLED']);

        $customer = $this->customer();

        $this->bookingPage($vendor, $customer, ['service' => $service->id])
            ->set('eventName', 'Bridal makeup')
            ->set('eventDate', $date)
            ->set('startTime', '11:00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertSame(1, ServiceBooking::query()->where('customer_id', $customer->id)->count());
    }

    public function test_hourly_overlap_is_rejected_but_a_free_range_is_not(): void
    {
        $vendor = $this->vendor('lens-and-lantern-studio'); // photography → HOURLY
        $service = $this->makeService($vendor, ['name' => 'Cinematic Coverage', 'price_from' => 12000]);
        $date = now()->addDays(35)->toDateString();

        ServiceBooking::create([
            'tenant_id' => $vendor->id,
            'vendor_category_id' => $vendor->vendor_category_id,
            'customer_id' => $this->customer()->id,
            'booking_reference' => 'RG-SB-HOURS1',
            'event_name' => 'Existing shoot',
            'event_date' => $date,
            'start_time' => '14:00',
            'hours' => 3,
            'quoted_amount' => 36000,
            'status' => 'CONFIRMED',
        ]);

        $customer = $this->customer();

        // 15:00–17:00 overlaps the existing 14:00–17:00 booking.
        $this->bookingPage($vendor, $customer, ['service' => $service->id])
            ->set('eventName', 'Overlapping shoot')
            ->set('eventDate', $date)
            ->set('hours', 2)
            ->set('startTime', '15:00')
            ->call('save')
            ->assertSet('errorMessage', 'That time range overlaps an existing booking (RG-SB-HOURS1). Please pick another slot.');

        $this->assertSame(0, ServiceBooking::query()->where('customer_id', $customer->id)->count());

        // 18:00–20:00 is free — and 12,000 × 2 hours = 24,000.
        $this->bookingPage($vendor, $customer, ['service' => $service->id])
            ->set('eventName', 'Free slot shoot')
            ->set('eventDate', $date)
            ->set('hours', 2)
            ->set('startTime', '18:00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $booking = ServiceBooking::query()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame(24000.0, (float) $booking->quoted_amount);
        $this->assertSame('18:00', $booking->start_time->format('H:i'));
    }

    // ─────────────────────────────────────────────────────────────
    // IDOR / cross-tenant guards
    // ─────────────────────────────────────────────────────────────

    public function test_service_id_from_another_vendor_is_ignored(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $foreign = $this->makeService($this->vendor('lens-and-lantern-studio'), ['price_from' => 999999]);
        $customer = $this->customer();

        $this->bookingPage($vendor, $customer, ['service' => $foreign->id])
            ->set('eventName', 'Cross tenant attempt')
            ->set('eventDate', now()->addDays(30)->toDateString())
            ->set('guestCount', 100)
            ->call('save')
            ->assertHasErrors(['eventName']);

        $this->assertSame(0, ServiceBooking::query()->count());
    }

    public function test_inactive_service_cannot_be_booked(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $service = $this->makeService($vendor, ['is_active' => false]);
        $customer = $this->customer();

        $this->bookingPage($vendor, $customer, ['service' => $service->id])
            ->set('eventName', 'Retired service')
            ->set('eventDate', now()->addDays(30)->toDateString())
            ->set('guestCount', 100)
            ->call('save')
            ->assertHasErrors(['eventName']);

        $this->assertSame(0, ServiceBooking::query()->count());
    }

    // ─────────────────────────────────────────────────────────────
    // Hand-off into the existing lifecycle
    // ─────────────────────────────────────────────────────────────

    public function test_booking_appears_in_the_customer_portal_and_vendor_board(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $service = $this->makeService($vendor, ['price_from' => 1850]);
        $customer = $this->customer();

        $this->bookingPage($vendor, $customer, ['service' => $service->id])
            ->set('eventName', 'Visible Booking')
            ->set('eventDate', now()->addDays(30)->toDateString())
            ->set('guestCount', 200)
            ->call('save')
            ->assertHasNoErrors();

        $reference = ServiceBooking::query()
            ->where('customer_id', $customer->id)->firstOrFail()->booking_reference;

        // Customer portal — quotes & services tab.
        Livewire::actingAs($customer)
            ->test(CustomerDashboard::class)
            ->call('switchTab', 'quotes')
            ->assertSee($reference)
            ->assertSee('Visible Booking');

        // Vendor bookings board — scoped to this tenant only.
        Livewire::actingAs($this->vendorOwner($vendor))
            ->test(VendorBookingsBoard::class)
            ->assertSee($reference);
    }

    public function test_vendor_board_does_not_leak_other_tenants_direct_bookings(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $service = $this->makeService($vendor, ['price_from' => 1850]);
        $customer = $this->customer();

        $this->bookingPage($vendor, $customer, ['service' => $service->id])
            ->set('eventName', 'Private Booking')
            ->set('eventDate', now()->addDays(30)->toDateString())
            ->set('guestCount', 200)
            ->call('save')
            ->assertHasNoErrors();

        $reference = ServiceBooking::query()
            ->where('customer_id', $customer->id)->firstOrFail()->booking_reference;

        $otherOwner = $this->vendorOwner($this->vendor('lens-and-lantern-studio'));

        Livewire::actingAs($otherOwner)
            ->test(VendorBookingsBoard::class)
            ->assertDontSee($reference);
    }

    public function test_vendor_can_confirm_a_direct_booking(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $service = $this->makeService($vendor, ['price_from' => 1850]);
        $customer = $this->customer();

        $this->bookingPage($vendor, $customer, ['service' => $service->id])
            ->set('eventName', 'Confirmable Booking')
            ->set('eventDate', now()->addDays(30)->toDateString())
            ->set('guestCount', 100)
            ->call('save')
            ->assertHasNoErrors();

        $booking = ServiceBooking::query()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame('ACCEPTED', $booking->status);

        Livewire::actingAs($this->vendorOwner($vendor))
            ->test(VendorBookingsBoard::class)
            ->call('confirmBooking', $booking->id);

        $booking->refresh();

        $this->assertSame('CONFIRMED', $booking->status);
        $this->assertNotNull($booking->confirmed_at);
        $this->assertGreaterThan(0, (float) $booking->advance_amount);
    }

    // ─────────────────────────────────────────────────────────────
    // Profile CTAs
    // ─────────────────────────────────────────────────────────────

    public function test_profile_shows_book_now_only_for_priced_items(): void
    {
        $vendor = $this->vendor('nawab-caterers');
        $vendor->update(['marketplace_visible' => true, 'verification_status' => 'APPROVED', 'is_active' => true]);
        $priced = $this->makeService($vendor, ['name' => 'Priced Feast', 'price_from' => 1850, 'sort' => 1]);
        $unpriced = $this->makeService($vendor, ['name' => 'Bespoke Feast', 'price_from' => null, 'price_to' => null, 'sort' => 2]);

        $html = $this->get(route('public.vendor-profile', ['slug' => $vendor->slug]))
            ->assertOk()
            ->getContent();

        $pricedLink = route('public.vendor-book', ['slug' => $vendor->slug, 'service' => $priced->id]);
        $unpricedLink = route('public.vendor-book', ['slug' => $vendor->slug, 'service' => $unpriced->id]);

        $this->assertStringContainsString($pricedLink, $html, 'Priced service is missing its Book Now CTA.');
        $this->assertStringNotContainsString($unpricedLink, $html, 'Unpriced service must stay quote-only.');
    }

    public function test_profile_shows_a_package_book_now_cta(): void
    {
        $vendor = $this->vendor('gulshan-decor-house');
        $vendor->update(['marketplace_visible' => true, 'verification_status' => 'APPROVED', 'is_active' => true]);
        $package = $this->makePackage($vendor);

        $html = $this->get(route('public.vendor-profile', ['slug' => $vendor->slug]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            route('public.vendor-book', ['slug' => $vendor->slug, 'package' => $package->id]),
            $html,
            'Priced package is missing its Book Now CTA.'
        );
        $this->assertStringContainsString('Book Now', $html);
    }
}
