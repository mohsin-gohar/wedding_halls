<?php

namespace Tests\Feature;

use App\Livewire\VendorBookingsBoard;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\KitchenKot;
use App\Models\Payment;
use App\Models\ServiceBooking;
use App\Models\ServicePayment;
use App\Models\StaffPayroll;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 1 cross-tenant isolation regression suite.
 *
 * Two unrelated tenants are used throughout: Tenant A (venue + catering
 * records) vs Tenant B (photography studio). Every direct-ID access path
 * (documents, API, Livewire actions) must fail closed for Tenant B.
 */
class CrossTenantIsolationRegressionTest extends TestCase
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

    private function ownerFor(Tenant $tenant): User
    {
        $owner = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->first();

        if ($owner) {
            return $owner;
        }

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner of '.$tenant->name,
            'email' => 'owner-'.$tenant->slug.'@example.test',
            'phone' => '+92 300 9999999',
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('secret123'),
        ]);
    }

    private function customer(): User
    {
        $existing = User::query()->where('role', 'ROLE_CUSTOMER')->first();

        if ($existing) {
            return $existing;
        }

        return User::create([
            'name' => 'Isolation Customer',
            'email' => 'isolation.customer@example.test',
            'phone' => '+92 300 7777777',
            'role' => 'ROLE_CUSTOMER',
            'password' => Hash::make('secret123'),
        ]);
    }

    private function venueHallAndTenant(): array
    {
        $hall = Hall::query()->firstOrFail();
        $tenant = Tenant::query()->findOrFail($hall->tenant_id);

        return [$hall, $tenant];
    }

    private function makeBooking(Tenant $tenant, Hall $hall, User $customer, string $reference): Booking
    {
        return Booking::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hall->id,
            'customer_id' => $customer->id,
            'booking_reference' => $reference,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'event_type' => 'Walima Reception',
            'event_date' => now()->addDays(30)->format('Y-m-d'),
            'slot_type' => 'EVENING',
            'guest_count' => 300,
            'per_head_rate' => 2000,
            'hall_rent' => 150000,
            'grand_total' => 750000,
            'advance_paid' => 200000,
            'balance_due' => 550000,
            'status' => 'CONFIRMED',
            'pipeline_stage' => 'CONFIRMED',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Documents — direct URL / IDOR
    // ─────────────────────────────────────────────────────────────

    public function test_cross_tenant_owner_cannot_download_booking_documents(): void
    {
        [$hall, $venueTenant] = $this->venueHallAndTenant();
        $customer = $this->customer();
        $booking = $this->makeBooking($venueTenant, $hall, $customer, 'BK-ISO-DOCS-001');

        $intruder = $this->ownerFor($this->photographyVendor());

        foreach (['agreement', 'quotation', 'invoice'] as $document) {
            $this->actingAs($intruder)
                ->get(route('documents.'.$document, $booking->id))
                ->assertStatus(403);
        }

        // The venue's own owner still has access (control case).
        $this->actingAs($this->ownerFor($venueTenant))
            ->get(route('documents.invoice', $booking->id))
            ->assertStatus(200);
    }

    public function test_cross_tenant_owner_cannot_download_payment_receipt(): void
    {
        [$hall, $venueTenant] = $this->venueHallAndTenant();
        $customer = $this->customer();
        $booking = $this->makeBooking($venueTenant, $hall, $customer, 'BK-ISO-RCPT-001');

        $payment = Payment::create([
            'tenant_id' => $venueTenant->id,
            'booking_id' => $booking->id,
            'amount' => 100000,
            'payment_type' => 'TOKEN_ADVANCE',
            'payment_method' => 'CASH',
            'receipt_no' => 'WH-RCP-ISO-0001',
        ]);

        $intruder = $this->ownerFor($this->photographyVendor());

        $this->actingAs($intruder)
            ->get(route('documents.receipt', $payment->id))
            ->assertStatus(403);

        $this->actingAs($this->ownerFor($venueTenant))
            ->get(route('documents.receipt', $payment->id))
            ->assertStatus(200);
    }

    public function test_salary_slip_is_restricted_to_employee_and_authorized_tenant_staff(): void
    {
        [$hall, $venueTenant] = $this->venueHallAndTenant();
        $customer = $this->customer();

        $employee = User::create([
            'tenant_id' => $venueTenant->id,
            'name' => 'Floor Staff One',
            'email' => 'floor.staff.one@example.test',
            'phone' => '+92 300 5551111',
            'role' => 'ROLE_FLOOR_STAFF',
            'password' => Hash::make('secret123'),
        ]);

        $colleague = User::create([
            'tenant_id' => $venueTenant->id,
            'name' => 'Floor Staff Two',
            'email' => 'floor.staff.two@example.test',
            'phone' => '+92 300 5552222',
            'role' => 'ROLE_FLOOR_STAFF',
            'password' => Hash::make('secret123'),
        ]);

        $payroll = StaffPayroll::create([
            'tenant_id' => $venueTenant->id,
            'user_id' => $employee->id,
            'month_year' => '2026-11',
            'basic_salary' => 45000,
            'net_salary' => 45000,
            'status' => 'APPROVED',
        ]);

        // The employee sees their own slip.
        $this->actingAs($employee)
            ->get(route('documents.salary-slip', $payroll->id))
            ->assertStatus(200);

        // A colleague of the same tenant without payroll rights is refused.
        $this->actingAs($colleague)
            ->get(route('documents.salary-slip', $payroll->id))
            ->assertStatus(403);

        // An owner of an unrelated tenant is refused.
        $this->actingAs($this->ownerFor($this->photographyVendor()))
            ->get(route('documents.salary-slip', $payroll->id))
            ->assertStatus(403);
    }

    public function test_customer_can_never_open_internal_kitchen_ticket(): void
    {
        [$hall, $venueTenant] = $this->venueHallAndTenant();
        $customer = $this->customer();
        $booking = $this->makeBooking($venueTenant, $hall, $customer, 'BK-ISO-KOT-001');

        $kot = KitchenKot::create([
            'tenant_id' => $venueTenant->id,
            'booking_id' => $booking->id,
            'kot_number' => 'KOT-ISO-0001',
            'guest_count' => 300,
        ]);

        // The customer who owns the booking must never see internal KOT data.
        $this->actingAs($customer)
            ->get(route('documents.kot', $kot->id))
            ->assertStatus(403);

        // Venue operational staff of the owning tenant may.
        $this->actingAs($this->ownerFor($venueTenant))
            ->get(route('documents.kot', $kot->id))
            ->assertStatus(200);
    }

    public function test_guest_is_redirected_from_private_documents(): void
    {
        [$hall, $venueTenant] = $this->venueHallAndTenant();
        $booking = $this->makeBooking($venueTenant, $hall, $this->customer(), 'BK-ISO-GUEST-001');

        $this->get(route('documents.invoice', $booking->id))
            ->assertRedirect(route('login'));
    }

    // ─────────────────────────────────────────────────────────────
    // API — direct-ID access
    // ─────────────────────────────────────────────────────────────

    public function test_api_cross_tenant_direct_id_access_is_not_found(): void
    {
        [$hall, $venueTenant] = $this->venueHallAndTenant();
        $booking = $this->makeBooking($venueTenant, $hall, $this->customer(), 'BK-ISO-API-001');

        $intruder = $this->ownerFor($this->photographyVendor());

        $this->actingAs($intruder)
            ->getJson('/api/v1/bookings/'.$booking->id)
            ->assertStatus(404);

        // The owning tenant can fetch the same record (control case).
        $this->actingAs($this->ownerFor($venueTenant))
            ->getJson('/api/v1/bookings/'.$booking->id)
            ->assertStatus(200);
    }

    public function test_api_cross_tenant_service_booking_direct_id_access_is_not_found(): void
    {
        $tenantA = $this->cateringVendor();
        $tenantB = $this->photographyVendor();

        $serviceBooking = ServiceBooking::create([
            'tenant_id' => $tenantA->id,
            'vendor_category_id' => $tenantA->vendor_category_id,
            'customer_id' => $this->customer()->id,
            'booking_reference' => 'RG-SB-ISO-API',
            'event_name' => 'Isolation catering gala',
            'event_date' => now()->addDays(35)->toDateString(),
            'quoted_amount' => 250000,
            'status' => 'ACCEPTED',
        ]);

        $this->actingAs($this->ownerFor($tenantB))
            ->getJson('/api/v1/service-bookings/'.$serviceBooking->id)
            ->assertStatus(404);

        $this->actingAs($this->ownerFor($tenantA))
            ->getJson('/api/v1/service-bookings/'.$serviceBooking->id)
            ->assertStatus(200);
    }

    public function test_api_cross_tenant_payment_recording_is_forbidden_and_persists_nothing(): void
    {
        $tenantA = $this->cateringVendor();
        $tenantB = $this->photographyVendor();

        $serviceBooking = ServiceBooking::create([
            'tenant_id' => $tenantA->id,
            'vendor_category_id' => $tenantA->vendor_category_id,
            'customer_id' => $this->customer()->id,
            'booking_reference' => 'RG-SB-ISO-PAY',
            'event_name' => 'Isolation payment attempt',
            'event_date' => now()->addDays(45)->toDateString(),
            'quoted_amount' => 180000,
            'status' => 'ACCEPTED',
        ]);

        $this->actingAs($this->ownerFor($tenantB))
            ->postJson('/api/v1/service-bookings/'.$serviceBooking->id.'/payments', [
                'amount' => 50000,
                'method' => 'CASH',
                'kind' => 'ADVANCE',
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('service_payments', [
            'service_booking_id' => $serviceBooking->id,
            'amount' => 50000,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Vendor ERP boards — listing + actions
    // ─────────────────────────────────────────────────────────────

    public function test_vendor_bookings_board_listing_is_tenant_scoped(): void
    {
        $tenantA = $this->cateringVendor();
        $tenantB = $this->photographyVendor();
        $customer = $this->customer();

        $bookingA = ServiceBooking::create([
            'tenant_id' => $tenantA->id,
            'vendor_category_id' => $tenantA->vendor_category_id,
            'customer_id' => $customer->id,
            'booking_reference' => 'RG-SB-ISO-BOARDA',
            'event_name' => 'Tenant A private banquet',
            'event_date' => now()->addDays(50)->toDateString(),
            'quoted_amount' => 320000,
            'status' => 'PENDING_VENDOR',
        ]);

        $bookingB = ServiceBooking::create([
            'tenant_id' => $tenantB->id,
            'vendor_category_id' => $tenantB->vendor_category_id,
            'customer_id' => $customer->id,
            'booking_reference' => 'RG-SB-ISO-BOARDB',
            'event_name' => 'Tenant B private shoot',
            'event_date' => now()->addDays(50)->toDateString(),
            'quoted_amount' => 90000,
            'status' => 'PENDING_VENDOR',
        ]);

        // End-to-end through the real route: Tenant B's owner sees their own
        // booking and never Tenant A's (computed props are not viewData).
        $this->actingAs($this->ownerFor($tenantB))
            ->get(route('vendor.bookings'))
            ->assertOk()
            ->assertSee($bookingB->booking_reference)
            ->assertDontSee($bookingA->booking_reference);
    }

    public function test_vendor_bookings_board_actions_cannot_touch_foreign_bookings(): void
    {
        $tenantA = $this->cateringVendor();
        $tenantB = $this->photographyVendor();

        $bookingA = ServiceBooking::create([
            'tenant_id' => $tenantA->id,
            'vendor_category_id' => $tenantA->vendor_category_id,
            'customer_id' => $this->customer()->id,
            'booking_reference' => 'RG-SB-ISO-ACTION',
            'event_name' => 'Tenant A confirm target',
            'event_date' => now()->addDays(55)->toDateString(),
            'quoted_amount' => 210000,
            'status' => 'PENDING_VENDOR',
        ]);

        // Tenant B's owner fires a confirm action at Tenant A's booking.
        Livewire::actingAs($this->ownerFor($tenantB))
            ->test(VendorBookingsBoard::class)
            ->call('confirmBooking', $bookingA->id)
            ->assertStatus(404);

        $bookingA->refresh();
        $this->assertSame('PENDING_VENDOR', $bookingA->status, 'Cross-tenant action must not mutate the booking.');
    }

    // ─────────────────────────────────────────────────────────────
    // Private payment proofs
    // ─────────────────────────────────────────────────────────────

    public function test_cross_tenant_payment_proof_download_is_forbidden(): void
    {
        $tenantA = $this->cateringVendor();
        $tenantB = $this->photographyVendor();

        $serviceBooking = ServiceBooking::create([
            'tenant_id' => $tenantA->id,
            'vendor_category_id' => $tenantA->vendor_category_id,
            'customer_id' => $this->customer()->id,
            'booking_reference' => 'RG-SB-ISO-PROOF',
            'event_name' => 'Isolation proof booking',
            'event_date' => now()->addDays(60)->toDateString(),
            'quoted_amount' => 150000,
            'status' => 'ACCEPTED',
        ]);

        $proof = ServicePayment::create([
            'tenant_id' => $tenantA->id,
            'service_booking_id' => $serviceBooking->id,
            'customer_id' => $serviceBooking->customer_id,
            'amount' => 50000,
            'method' => 'CASH',
            'status' => 'PENDING',
            'kind' => 'ADVANCE',
            'proof_path' => 'service-proofs/isolation-proof.png',
        ]);

        // Unauthenticated guest is refused by the auth layer first (redirect
        // to login) — must be asserted BEFORE any actingAs in this test.
        $this->get(route('documents.vendor-payment-proof', $proof->id))
            ->assertRedirect(route('login'));

        // Unrelated tenant owner is refused before the file layer is touched.
        $this->actingAs($this->ownerFor($tenantB))
            ->get(route('documents.vendor-payment-proof', $proof->id))
            ->assertStatus(403);

        // The owning vendor passes authorization (file itself is absent here,
        // so the controller answers 404 — proof that the 403 is authz-based).
        $this->actingAs($this->ownerFor($tenantA))
            ->get(route('documents.vendor-payment-proof', $proof->id))
            ->assertStatus(404);
    }
}
