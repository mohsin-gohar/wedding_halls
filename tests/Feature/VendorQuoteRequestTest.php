<?php

namespace Tests\Feature;

use App\Livewire\VendorQuoteRequest;
use App\Models\RFQ;
use App\Models\RfqTarget;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorCategory;
use App\Models\VendorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Marketplace quote-request flow (Phase 2/5).
 * Covers guest redirect, category-aware validation, RFQ + rfq_target creation
 * and cross-vendor service-ID tamper protection.
 */
class VendorQuoteRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function marketplaceVendor(string $categorySlug): Tenant
    {
        $category = VendorCategory::query()->where('slug', $categorySlug)->firstOrFail();

        return Tenant::query()
            ->where('vendor_category_id', $category->id)
            ->where('marketplace_visible', true)
            ->where('verification_status', 'APPROVED')
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'ROLE_CUSTOMER']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $vendor = $this->marketplaceVendor('catering');

        $this->get(route('public.vendor-quote', ['slug' => $vendor->slug]))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_customer_sees_quote_form(): void
    {
        $vendor = $this->marketplaceVendor('catering');

        $this->actingAs($this->customer())
            ->get(route('public.vendor-quote', ['slug' => $vendor->slug]))
            ->assertStatus(200)
            ->assertSee('Request a Free Quote');
    }

    public function test_hidden_or_unverified_vendor_returns_404(): void
    {
        $vendor = $this->marketplaceVendor('catering');
        $vendor->forceFill(['marketplace_visible' => false])->save();

        $this->actingAs($this->customer())
            ->get(route('public.vendor-quote', ['slug' => $vendor->slug]))
            ->assertStatus(404);
    }

    public function test_customer_can_request_quote_from_caterer(): void
    {
        $customer = $this->customer();
        $vendor = $this->marketplaceVendor('catering'); // booking_mode = PER_HEAD
        $service = VendorService::query()
            ->where('tenant_id', $vendor->id)
            ->where('is_active', true)
            ->firstOrFail();

        Livewire::actingAs($customer)
            ->test(VendorQuoteRequest::class, ['slug' => $vendor->slug])
            ->set('service', $service->id)
            ->set('eventName', 'Walima Dinner — Ali & Fatima')
            ->set('eventDate', now()->addDays(45)->toDateString())
            ->set('guestCount', 350)
            ->set('budgetRange', '150k-300k')
            ->set('requirements', 'Mughlai menu with live BBQ counter, service staff included.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $rfq = RFQ::query()
            ->where('client_id', $customer->id)
            ->where('tenant_id', $vendor->id)
            ->firstOrFail();

        $this->assertSame('OPEN', $rfq->status);
        $this->assertSame('Walima Dinner — Ali & Fatima', $rfq->title);
        $this->assertSame(350, $rfq->guest_count);
        $this->assertSame($service->id, $rfq->preferences['service_id']);
        $this->assertSame('PER_HEAD', $rfq->preferences['booking_mode']);
        $this->assertSame('PKR 150,000 – 300,000', $rfq->budget_range);

        // The targeted vendor must see it on their quotes board.
        $this->assertTrue(
            RfqTarget::query()->where('rfq_id', $rfq->id)->where('tenant_id', $vendor->id)->exists()
        );
    }

    public function test_per_head_category_requires_guest_count(): void
    {
        $customer = $this->customer();
        $vendor = $this->marketplaceVendor('catering');

        Livewire::actingAs($customer)
            ->test(VendorQuoteRequest::class, ['slug' => $vendor->slug])
            ->set('eventName', 'Mehndi Lunch')
            ->set('eventDate', now()->addDays(20)->toDateString())
            ->call('save')
            ->assertHasErrors(['guestCount']);

        $this->assertSame(0, RFQ::query()->where('client_id', $customer->id)->count());
    }

    public function test_hourly_category_requires_hours(): void
    {
        $customer = $this->customer();
        $vendor = $this->marketplaceVendor('photography'); // booking_mode = HOURLY

        Livewire::actingAs($customer)
            ->test(VendorQuoteRequest::class, ['slug' => $vendor->slug])
            ->set('eventName', 'Pre-Wedding Shoot')
            ->set('eventDate', now()->addDays(15)->toDateString())
            ->call('save')
            ->assertHasErrors(['hours']);

        $this->assertSame(0, RFQ::query()->where('client_id', $customer->id)->count());
    }

    public function test_past_event_date_is_rejected(): void
    {
        $customer = $this->customer();
        $vendor = $this->marketplaceVendor('decoration');

        Livewire::actingAs($customer)
            ->test(VendorQuoteRequest::class, ['slug' => $vendor->slug])
            ->set('eventName', 'Baraat Stage')
            ->set('eventDate', now()->subDay()->toDateString())
            ->call('save')
            ->assertHasErrors(['eventDate']);

        $this->assertSame(0, RFQ::query()->where('client_id', $customer->id)->count());
    }

    public function test_tampered_service_id_from_another_vendor_is_ignored(): void
    {
        $customer = $this->customer();
        $vendor = $this->marketplaceVendor('catering');
        $foreignService = VendorService::query()
            ->where('tenant_id', '!=', $vendor->id)
            ->where('is_active', true)
            ->firstOrFail();

        Livewire::actingAs($customer)
            ->test(VendorQuoteRequest::class, ['slug' => $vendor->slug])
            ->set('service', $foreignService->id)
            ->set('eventName', 'Walima Feast')
            ->set('eventDate', now()->addDays(30)->toDateString())
            ->set('guestCount', 200)
            ->call('save')
            ->assertHasNoErrors();

        $rfq = RFQ::query()->where('client_id', $customer->id)->firstOrFail();

        // A foreign service ID must never be attached to this vendor's RFQ.
        $this->assertNull($rfq->preferences['service_id']);
        $this->assertNull($rfq->preferences['service_name']);
    }

    public function test_rate_limit_blocks_more_than_ten_requests_per_hour(): void
    {
        $customer = $this->customer();
        $vendor = $this->marketplaceVendor('catering');

        for ($i = 0; $i < 10; $i++) {
            Livewire::actingAs($customer)
                ->test(VendorQuoteRequest::class, ['slug' => $vendor->slug])
                ->set('eventName', 'Event '.$i)
                ->set('eventDate', now()->addDays(30)->toDateString())
                ->set('guestCount', 100 + $i)
                ->call('save')
                ->assertHasNoErrors();
        }

        // The 11th request inside the same hour is throttled.
        Livewire::actingAs($customer)
            ->test(VendorQuoteRequest::class, ['slug' => $vendor->slug])
            ->set('eventName', 'Event Overflow')
            ->set('eventDate', now()->addDays(30)->toDateString())
            ->set('guestCount', 120)
            ->call('save')
            ->assertHasErrors(['requirements']);

        $this->assertSame(10, RFQ::query()->where('client_id', $customer->id)->count());
    }
}
