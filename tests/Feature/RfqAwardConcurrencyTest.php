<?php

namespace Tests\Feature;

use App\Exceptions\ServiceBookingException;
use App\Livewire\VendorQuotesBoard;
use App\Models\RFQ;
use App\Models\RFQBid;
use App\Models\ServiceBooking;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorCategory;
use App\Services\ServiceBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RfqAwardConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_awarding_a_quote_creates_one_booking_and_closes_the_rfq(): void
    {
        [$tenant, $customer] = $this->makeVendorAndCustomer('beauty-bridal');
        $rfq = $this->makeRfq($tenant, $customer, ['start_time' => '11:00', 'hours' => 2]);
        $bid = $this->makeBid($rfq, 45000);

        $booking = app(ServiceBookingService::class)->acceptQuote($bid->id, (int) $customer->id);

        $this->assertSame((string) $bid->id, (string) $booking->rfq_bid_id);
        $this->assertSame((string) $tenant->id, (string) $booking->tenant_id);
        $this->assertSame('AWARDED', $rfq->fresh()->status);
        $this->assertSame('ACCEPTED', $bid->fresh()->status);
        $this->assertSame('11:00', $booking->fresh()->start_time->format('H:i'));
        $this->assertSame(1, ServiceBooking::query()->where('rfq_bid_id', $bid->id)->count());
    }

    public function test_second_bid_on_the_same_rfq_cannot_be_awarded_after_the_first(): void
    {
        [$tenant, $customer] = $this->makeVendorAndCustomer('beauty-bridal');
        $rfq = $this->makeRfq($tenant, $customer, ['start_time' => '12:00']);
        $first = $this->makeBid($rfq, 40000);
        $second = $this->makeBid($rfq, 38000);

        app(ServiceBookingService::class)->acceptQuote($first->id, (int) $customer->id);

        try {
            app(ServiceBookingService::class)->acceptQuote($second->id, (int) $customer->id);
            $this->fail('A second bid on an awarded RFQ must not be accepted.');
        } catch (ServiceBookingException $e) {
            $this->assertStringContainsString('already been awarded', $e->getMessage());
        }

        $this->assertSame('REJECTED', $second->fresh()->status);
        $this->assertSame(1, ServiceBooking::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_awarding_a_quote_is_blocked_when_the_time_slot_is_taken(): void
    {
        [$tenant, $customer] = $this->makeVendorAndCustomer('beauty-bridal');
        $rfq = $this->makeRfq($tenant, $customer, ['start_time' => '14:00', 'hours' => 2]);
        $bid = $this->makeBid($rfq, 50000);

        $existing = ServiceBooking::create([
            'tenant_id' => $tenant->id,
            'vendor_category_id' => $tenant->vendor_category_id,
            'customer_id' => $customer->id,
            'booking_reference' => ServiceBooking::generateReference(),
            'event_name' => 'Existing appointment',
            'event_date' => $rfq->event_date->toDateString(),
            'start_time' => '14:00',
            'hours' => 2,
            'quoted_amount' => 10000,
            'status' => 'CONFIRMED',
        ]);

        $this->assertSame('APPOINTMENT', $tenant->fresh()->vendorCategory->booking_mode);
        $this->assertSame($rfq->event_date->toDateString(), $existing->event_date->toDateString());

        try {
            app(ServiceBookingService::class)->acceptQuote($bid->id, (int) $customer->id);
            $this->fail('An overlapping appointment must not be awarded.');
        } catch (ServiceBookingException $e) {
            $this->assertStringContainsString('already booked', $e->getMessage());
        }

        $this->assertSame('OPEN', $rfq->fresh()->status);
        $this->assertSame('PENDING', $bid->fresh()->status);
        $this->assertSame(1, ServiceBooking::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_vendor_cannot_award_a_bid_from_another_tenants_rfq(): void
    {
        [$tenantA, $customerA] = $this->makeVendorAndCustomer('beauty-bridal');
        [$tenantB, $customerB] = $this->makeVendorAndCustomer('beauty-bridal');

        $ownerA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'ROLE_TENANT_OWNER',
            'status' => 'ACTIVE',
        ]);
        $ownerA->assignRole('ROLE_TENANT_OWNER');

        $rfqB = $this->makeRfq($tenantB, $customerB, ['start_time' => '10:00']);
        $bidB = $this->makeBid($rfqB, 25000);

        Livewire::actingAs($ownerA->fresh())
            ->test(VendorQuotesBoard::class)
            ->call('awardQuote', $bidB->id)
            ->assertStatus(404);

        $this->assertSame('OPEN', $rfqB->fresh()->status);
        $this->assertSame('PENDING', $bidB->fresh()->status);
        $this->assertSame(0, ServiceBooking::query()->count());
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function makeVendorAndCustomer(string $categorySlug): array
    {
        $category = VendorCategory::query()->where('slug', $categorySlug)->firstOrFail();

        $tenant = Tenant::create([
            'name' => 'Award Test '.$categorySlug.' '.uniqid(),
            'slug' => 'award-test-'.uniqid(),
            'vendor_category_id' => $category->id,
            'email' => 'award@riwaj.pk',
            'phone' => '+92 300 1112233',
            'city' => 'Lahore',
            'subscription_plan' => 'STARTER',
            'currency' => 'PKR',
            'verification_status' => 'APPROVED',
            'is_active' => true,
            'marketplace_visible' => true,
        ]);

        $customer = User::factory()->create([
            'role' => 'ROLE_CUSTOMER',
            'status' => 'ACTIVE',
        ]);

        return [$tenant, $customer];
    }

    private function makeRfq(Tenant $tenant, User $customer, array $preferences): RFQ
    {
        return RFQ::create([
            'client_id' => $customer->id,
            'tenant_id' => $tenant->id,
            'vendor_category_id' => $tenant->vendor_category_id,
            'title' => 'Bridal makeup appointment',
            'event_date' => now()->addDays(30)->toDateString(),
            'city' => 'Lahore',
            'guest_count' => 1,
            'budget_range' => 'PKR 50k',
            'requirements' => 'Full bridal package.',
            'status' => 'OPEN',
            'preferences' => array_merge([
                'service_name' => 'Bridal Makeup',
            ], $preferences),
        ]);
    }

    private function makeBid(RFQ $rfq, float $amount): RFQBid
    {
        return RFQBid::create([
            'rfq_id' => $rfq->id,
            'vendor_id' => User::factory()->create(['role' => 'ROLE_TENANT_OWNER'])->id,
            'bid_amount' => $amount,
            'proposal' => 'Complete bridal makeup and draping service.',
            'status' => 'PENDING',
        ]);
    }
}
