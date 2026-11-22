<?php

namespace Tests\Feature;

use App\Livewire\CalendarShiftGrid;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PortalRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_all_public_marketplace_pages_render_successfully(): void
    {
        $hall = Hall::first();

        // 1. Home
        $this->get('/')->assertStatus(200);

        // 2. Public Venues Marketplace
        $this->get('/venues')->assertStatus(200);

        // 3. Public Venue Details
        if ($hall) {
            $this->get('/venues/'.$hall->slug)->assertStatus(200);
        }

        // 4. Public Menus & Packages
        $this->get('/menus')->assertStatus(200);
        $this->get('/packages')->assertStatus(200);

        // 5. Visual Gallery, Services, About, Contact, FAQs
        $this->get('/gallery')->assertStatus(200);
        $this->get('/services')->assertStatus(200);
        $this->get('/about')->assertStatus(200);
        $this->get('/contact')->assertStatus(200);
        $this->get('/faqs')->assertStatus(200);

        // 6. Public Availability & RSVP
        $this->get('/check-availability')->assertStatus(200);
        $this->get('/rsvp')->assertStatus(200);

        // 7. Legal Policies
        $this->get('/policies/terms')->assertStatus(200);
        $this->get('/policies/privacy')->assertStatus(200);
        $this->get('/policies/cancellation')->assertStatus(200);

        // 8. Auth Pages (Guest access)
        $this->get('/login')->assertStatus(200);
        $this->get('/register')->assertStatus(200);
        $this->get('/customer/login')->assertStatus(200);
        $this->get('/customer/register')->assertStatus(200);
    }

    public function test_unauthenticated_guests_are_redirected_away_from_protected_suites(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/calendar')->assertRedirect(route('login'));
        $this->get('/kitchen-kots')->assertRedirect(route('login'));
        $this->get('/utility-fuel')->assertRedirect(route('login'));
        $this->get('/seating-planner')->assertRedirect(route('login'));
        $this->get('/whatsapp-rsvps')->assertRedirect(route('login'));
        $this->get('/hall/media')->assertRedirect(route('login'));
        $this->get('/customer/dashboard')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_authenticated_vendor_owner_can_access_executive_suite(): void
    {
        $user = User::where('role', 'ROLE_TENANT_OWNER')->first() ?: User::first();
        $this->actingAs($user);

        // 1. Dashboard (Weekly/Monthly Analytics)
        $this->get('/dashboard')->assertStatus(200);

        // 2. Shift Calendar Grid
        $this->get('/calendar')->assertStatus(200);

        // 3. Chef Kitchen KOTs (hidden when the kitchen module flag is off)
        if (config('platform.kitchen_module_enabled')) {
            $this->get('/kitchen-kots')->assertStatus(200);
        } else {
            $this->get('/kitchen-kots')->assertRedirect();
        }

        // 4. Utility & Fuel Logs
        $this->get('/utility-fuel')->assertStatus(200);

        // 5. Seating Floor Plan
        $this->get('/seating-planner')->assertStatus(200);

        // 6. WhatsApp RSVP Manager
        $this->get('/whatsapp-rsvps')->assertStatus(200);

        // 7. Hall Media Manager
        $this->get('/hall/media')->assertStatus(200);
    }

    public function test_customer_portal_and_super_admin_consoles_render_for_authorized_users(): void
    {
        // 1. Customer User
        $customer = User::create([
            'name' => 'Sara Khan',
            'email' => 'sara@example.com',
            'phone' => '+92 300 7776655',
            'role' => 'ROLE_CUSTOMER',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($customer)->get('/customer/dashboard')->assertStatus(200);

        // 2. Super Admin User
        $admin = User::where('role', 'ROLE_SUPER_ADMIN')->first() ?: User::create([
            'name' => 'System Admin',
            'email' => 'superadmin.test@riwajgrand.pk',
            'phone' => '+92 300 1111111',
            'role' => 'ROLE_SUPER_ADMIN',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($admin)->get('/admin')->assertStatus(200);
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    }

    public function test_super_admin_calendar_can_select_global_halls_and_expands_full_day_bookings(): void
    {
        $admin = User::where('role', 'ROLE_SUPER_ADMIN')->first() ?: User::create([
            'name' => 'Calendar Admin',
            'email' => 'calendar-admin@example.com',
            'role' => 'ROLE_SUPER_ADMIN',
            'password' => Hash::make('secret123'),
        ]);
        $hall = Hall::where('is_active', true)->firstOrFail();
        // Use a fixed date far in the future so it cannot collide with seeder
        // bookings near today (today/tomorrow/day-after) regardless of timezone.
        $date = now()->addDays(10)->format('Y-m-d');

        $booking = Booking::create([
            'tenant_id' => $hall->tenant_id,
            'hall_id' => $hall->id,
            'customer_name' => 'Full Day Calendar Host',
            'customer_phone' => '03001112233',
            'event_type' => 'Wedding',
            'event_date' => $date,
            'slot_type' => 'FULL_DAY',
            'status' => 'CONFIRMED',
            'guest_count' => 200,
            'grand_total' => 150000,
        ]);

        Livewire::actingAs($admin)->test(CalendarShiftGrid::class)
            ->set('currentMonth', now()->addDays(10)->format('Y-m'))
            ->set('selectedHallId', $hall->id)
            ->assertViewHas('halls', fn ($halls) => $halls->contains('id', $hall->id))
            ->assertViewHas('bookings', function ($bookings) use ($date) {
                if (isset($bookings[$date.'_AFTERNOON'], $bookings[$date.'_EVENING'], $bookings[$date.'_NIGHT'])) {
                    return true;
                }

                return false;
            });
    }
}
