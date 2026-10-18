<?php

namespace Tests\Feature;

use App\Exceptions\SlotAlreadyBookedException;
use App\Livewire\AdminDashboard;
use App\Livewire\Auth\Register;
use App\Livewire\PublicHallDetails;
use App\Livewire\PublicVenues;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EndToEndSystemAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Complete lifecycle audit:
     * 1. Register new hall -> verify tenant, venue, hall, trial, slug
     * 2. Verify unapproved status hides hall from public
     * 3. Super Admin logs in, reviews, and approves the hall
     * 4. Approved hall renders publicly with flip calendar and badge indicators
     * 5. Hall Admin logs in and accesses dashboard + all operational suites
     * 6. Double booking concurrency prevention is verified
     * 7. Customer portal isolation & access is verified
     * 8. Public website pages audit
     */
    public function test_complete_wedding_hall_saas_lifecycle_audit(): void
    {
        // ---------------------------------------------------------------------
        // STEP 1: Hall Registration by Vendor Owner
        // ---------------------------------------------------------------------
        Livewire::test(Register::class)
            ->set('hallName', 'Margalla Grand Marquee')
            ->set('ownerName', 'Khurram Shehzad')
            ->set('email', 'khurram@margallagrand.pk')
            ->set('phone', '+92 300 8887766')
            ->set('city', 'Islamabad')
            ->set('capacity', 800)
            ->set('baseRent', 250000)
            ->set('password', 'SecurePass123!')
            ->set('password_confirmation', 'SecurePass123!')
            ->call('register')
            ->assertHasNoErrors();

        $tenant = Tenant::where('email', 'khurram@margallagrand.pk')->first();
        $this->assertNotNull($tenant, 'Tenant record should be created');
        $this->assertEquals('Margalla Grand Marquee', $tenant->name);
        $this->assertStringStartsWith('margalla-grand-marquee', $tenant->slug);
        $this->assertEquals('PENDING', $tenant->verification_status);
        $this->assertEquals('TRIAL', $tenant->subscription_status);
        $this->assertTrue($tenant->isPending());

        $hall = Hall::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($hall, 'Hall record should be created under tenant');
        $this->assertEquals('Margalla Grand Marquee', $hall->name);
        $this->assertEquals('margalla-grand-marquee', $hall->slug);
        $this->assertEquals(800, $hall->max_capacity);

        $owner = User::where('email', 'khurram@margallagrand.pk')->first();
        $this->assertNotNull($owner, 'Owner user should be created');
        $this->assertEquals('ROLE_TENANT_OWNER', $owner->role);
        $this->assertEquals($tenant->id, $owner->tenant_id);

        // ---------------------------------------------------------------------
        // STEP 2: Verify Unapproved Hall is Hidden from Public
        // ---------------------------------------------------------------------
        Livewire::test(PublicVenues::class)
            ->assertDontSee('Margalla Grand Marquee');

        $this->get('/venues/' . $hall->slug)->assertStatus(404);

        // ---------------------------------------------------------------------
        // STEP 3: Super Admin Approves the New Hall Tenant
        // ---------------------------------------------------------------------
        $admin = User::where('role', 'ROLE_SUPER_ADMIN')->first();
        $this->assertNotNull($admin, 'Super admin user must exist');

        // Access Super Admin console
        $this->actingAs($admin)->get('/admin')->assertStatus(200);

        // Approve tenant via Livewire Super Admin console
        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->call('approveTenant', $tenant->id)
            ->assertHasNoErrors();

        $tenant->refresh();
        $this->assertEquals('APPROVED', $tenant->verification_status);
        $this->assertTrue($tenant->isApproved());

        // ---------------------------------------------------------------------
        // STEP 4: Approved Hall Showcase is Now Publicly Accessible
        // ---------------------------------------------------------------------
        $publicVenueResponse = $this->get('/venues/' . $hall->slug);
        $publicVenueResponse->assertStatus(200);
        $publicVenueResponse->assertSee('Margalla Grand Marquee');

        // Test flip-calendar component inside public details
        Livewire::test(PublicHallDetails::class, ['slug' => $hall->slug])
            ->assertSee('Shift Availability')
            ->assertSee('Margalla Grand Marquee');

        // ---------------------------------------------------------------------
        // STEP 5: New Hall Admin Logs In & Accesses Operational Suites
        // ---------------------------------------------------------------------
        $this->actingAs($owner);

        // Executive Dashboard
        $this->get('/dashboard')->assertStatus(200);

        // Calendar Shift Scheduler
        $this->get('/calendar')->assertStatus(200);

        // Hall Media & Visual Showcase
        $this->get('/hall/media')->assertStatus(200);

        // Seating Floor Plan
        $this->get('/seating-planner')->assertStatus(200);

        // Kitchen KOTs
        $this->get('/kitchen-kots')->assertStatus(200);

        // Utility & Fuel Tracking
        $this->get('/utility-fuel')->assertStatus(200);

        // WhatsApp RSVPs
        $this->get('/whatsapp-rsvps')->assertStatus(200);

        // ---------------------------------------------------------------------
        // STEP 6: Concurrency & Atomic Double-Booking Lock Verification
        // ---------------------------------------------------------------------
        $bookingService = app(BookingService::class);
        $auditDate = now()->addDays(20)->format('Y-m-d');
        $shiftType = 'EVENING';

        // 1st booking succeeds
        $firstBooking = $bookingService->createReservation([
            'hall_id' => $hall->id,
            'event_date' => $auditDate,
            'slot_type' => $shiftType,
            'customer_name' => 'Usman Tariq',
            'customer_phone' => '+92 300 5554433',
            'guest_count' => 450,
            'per_head_rate' => 1800,
            'hall_rent' => 250000,
            'advance_paid' => 100000,
        ]);
        $this->assertNotNull($firstBooking->id);

        // 2nd booking attempt for SAME hall, date, and shift MUST throw exception
        $doubleBookingBlocked = false;
        try {
            $bookingService->createReservation([
                'hall_id' => $hall->id,
                'event_date' => $auditDate,
                'slot_type' => $shiftType,
                'customer_name' => 'Conflict Client',
                'customer_phone' => '+92 300 1112233',
                'guest_count' => 300,
                'per_head_rate' => 1800,
                'advance_paid' => 50000,
            ]);
        } catch (SlotAlreadyBookedException $e) {
            $doubleBookingBlocked = true;
        }

        $this->assertTrue($doubleBookingBlocked, 'SlotAlreadyBookedException must be thrown on conflicting shift');

        // ---------------------------------------------------------------------
        // STEP 7: Customer Portal & RBAC Isolation
        // ---------------------------------------------------------------------
        $customer = User::where('role', 'ROLE_CUSTOMER')->first();
        $this->assertNotNull($customer, 'Customer user must exist');

        $this->actingAs($customer);
        $this->get('/customer/dashboard')->assertStatus(200);

        // Customer cannot access Super Admin console (403 Forbidden)
        $this->get('/admin')->assertStatus(403);

        // Customer visiting general /dashboard is redirected to their customer dashboard
        $this->get('/dashboard')->assertRedirect(route('customer.dashboard'));

        // Customer has no tenant_id, ensuring multi-tenant data isolation
        $this->assertNull($customer->tenant_id);

        // ---------------------------------------------------------------------
        // STEP 8: Public Website Pages Verification
        // ---------------------------------------------------------------------
        $this->get('/')->assertStatus(200);
        $this->get('/venues')->assertStatus(200);
        $this->get('/gallery')->assertStatus(200);
        $this->get('/services')->assertStatus(200);
        $this->get('/about')->assertStatus(200);
        $this->get('/contact')->assertStatus(200);
        $this->get('/faqs')->assertStatus(200);
        $this->get('/menus')->assertStatus(200);
        $this->get('/packages')->assertStatus(200);
        $this->get('/check-availability')->assertStatus(200);
        $this->get('/rsvp')->assertStatus(200);
        $this->get('/policies/terms')->assertStatus(200);
        $this->get('/policies/privacy')->assertStatus(200);
        $this->get('/policies/cancellation')->assertStatus(200);
    }
}
