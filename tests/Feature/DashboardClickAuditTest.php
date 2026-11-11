<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Livewire\Auth\Login;
use App\Livewire\CalendarShiftGrid;
use App\Livewire\CustomerDashboard;
use App\Livewire\Dashboard;
use App\Livewire\ExpenseTracker;
use App\Livewire\FloorPlanDesigner;
use App\Livewire\HallMediaManager;
use App\Livewire\InventoryTracker;
use App\Livewire\KitchenKotBoard;
use App\Livewire\PackageManager;
use App\Livewire\StaffAttendance;
use App\Livewire\StaffManager;
use App\Livewire\StaffPayroll;
use App\Livewire\UtilityFuelTracker;
use App\Livewire\WhatsAppRsvpManager;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardClickAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_seeded_demo_logins_land_on_the_correct_dashboards(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'owner@venuecloud.pk')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->post(route('logout'));

        Livewire::test(Login::class)
            ->set('email', 'customer@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('customer.dashboard'));

        $this->post(route('logout'));

        Livewire::test(Login::class)
            ->set('email', 'admin@eventhub.com')
            ->set('password', 'admin1235')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_vendor_owner_can_open_every_erp_page_and_click_core_actions(): void
    {
        $owner = User::where('email', 'owner@venuecloud.pk')->firstOrFail();
        $hall = Hall::where('tenant_id', $owner->tenant_id)->firstOrFail();
        $booking = Booking::where('tenant_id', $owner->tenant_id)->firstOrFail();

        $this->actingAs($owner);
        $this->get(route('customer.dashboard'))->assertRedirect(route('dashboard'));
        $this->get(route('admin.dashboard'))->assertForbidden();

        $pages = [
            '/dashboard' => 'Analytics',
            '/calendar' => 'Shift',
            '/hall/media' => 'Hall',
            '/expenses' => 'Expense',
            '/staff' => 'Staff',
            '/staff/attendance' => 'Attendance',
            '/staff/payroll' => 'Payroll',
            '/inventory' => 'Inventory',
            '/catering-packages' => 'Package',
            '/utility-fuel' => 'Fuel',
            '/seating-planner' => 'Floor',
            '/whatsapp-rsvps' => 'RSVP',
        ];

        foreach ($pages as $uri => $needle) {
            $this->get($uri)
                ->assertOk()
                ->assertSee($needle, false);
        }

        $this->get(route('hall.showcase', $hall->slug))->assertOk();
        $this->get(route('documents.agreement', $booking->id))->assertOk();

        Livewire::actingAs($owner)->test(Dashboard::class)
            ->call('setTimeframe', 'WEEKLY')
            ->assertSet('timeframe', 'WEEKLY')
            ->call('setTimeframe', 'MONTHLY')
            ->assertSet('timeframe', 'MONTHLY')
            ->call('setChartMetric', 'programs')
            ->assertSet('chartMetric', 'programs')
            ->call('previousOperationsMonth')
            ->call('nextOperationsMonth')
            ->call('openDailyExpenseModal', 'GENERATOR_DIESEL')
            ->assertSet('showDailyExpenseModal', true)
            ->call('closeDailyExpenseModal')
            ->assertSet('showDailyExpenseModal', false)
            ->assertOk();

        Livewire::actingAs($owner)->test(CalendarShiftGrid::class)
            ->call('nextMonth')
            ->call('prevMonth')
            ->call('goToToday')
            ->call('viewBookingDetails', $booking->id)
            ->call('closeDetailsModal')
            ->assertOk();

        Livewire::actingAs($owner)->test(HallMediaManager::class)
            ->call('openCreateModal')
            ->call('closeCreateModal')
            ->assertOk();

        Livewire::actingAs($owner)->test(ExpenseTracker::class)
            ->call('openCreateModal')
            ->assertOk();

        $staff = User::where('tenant_id', $owner->tenant_id)
            ->where('id', '!=', $owner->id)
            ->where('role', '!=', 'ROLE_SUPER_ADMIN')
            ->first();

        Livewire::actingAs($owner)->test(StaffManager::class)
            ->call('openCreateModal')
            ->call('closeCreateModal');

        if ($staff) {
            Livewire::actingAs($owner)->test(StaffManager::class)
                ->call('openProfileModal', $staff->id)
                ->call('closeProfileModal')
                ->call('openEditModal', $staff->id)
                ->call('closeEditModal');
        }

        Livewire::actingAs($owner)->test(StaffPayroll::class)
            ->call('generateMonthlyPayroll')
            ->assertOk();

        Livewire::actingAs($owner)->test(StaffAttendance::class)
            ->call('nextMonth')
            ->call('previousMonth')
            ->assertOk();

        Livewire::actingAs($owner)->test(StaffPayroll::class)->assertOk();
        Livewire::actingAs($owner)->test(InventoryTracker::class)
            ->call('setTab', 'stock')
            ->assertOk();
        Livewire::actingAs($owner)->test(PackageManager::class)
            ->call('openCreateModal')
            ->assertOk();
        if (config('platform.kitchen_module_enabled')) {
            $this->get('/kitchen-kots')->assertOk()->assertSee('Kitchen', false);
            Livewire::actingAs($owner)->test(KitchenKotBoard::class)->assertOk();
        }
        Livewire::actingAs($owner)->test(UtilityFuelTracker::class)->assertOk();
        Livewire::actingAs($owner)->test(FloorPlanDesigner::class)->assertOk();
        Livewire::actingAs($owner)->test(WhatsAppRsvpManager::class)->assertOk();
    }

    public function test_customer_dashboard_opens_booking_details_and_document_links(): void
    {
        $customer = User::where('email', 'customer@example.com')->firstOrFail();
        $booking = Booking::where('customer_email', $customer->email)
            ->orWhere('customer_id', $customer->id)
            ->first();

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('My Wedding', false);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertRedirect(route('customer.dashboard'));

        $component = Livewire::actingAs($customer)->test(CustomerDashboard::class)
            ->assertOk();

        if ($booking) {
            $component->call('viewBooking', $booking->id)
                ->assertSet('selectedBookingId', $booking->id)
                ->call('closeBookingModal')
                ->assertSet('selectedBookingId', null);

            $this->get(route('documents.agreement', $booking->id))->assertOk();
            $this->get(route('documents.invoice', $booking->id))->assertOk();
        }
    }

    public function test_admin_dashboard_tabs_and_modals_open_without_errors(): void
    {
        $admin = User::where('email', 'admin@eventhub.com')->firstOrFail();
        $this->assertNull($admin->tenant_id);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Platform Super Admin Console', false)
            ->assertDontSee('SaaS Plans & Pricing', false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['tab' => 'tasks']))
            ->assertOk()
            ->assertSee('Review pending hall KYC', false)
            ->assertSee('Follow up Riwaj Grand contact-form', false)
            ->assertSee('Concierge Inquiries', false);

        $tabs = ['vendors', 'tasks', 'traffic', 'halls', 'bookings', 'users', 'audit'];

        $component = Livewire::actingAs($admin)->test(AdminDashboard::class);

        foreach ($tabs as $tab) {
            $component->call('setTab', $tab)->assertSet('activeTab', $tab);
        }

        $component->call('setTab', 'plans')->call('setTab', 'payments');

        $component->call('setChartTimeframe', 'WEEKLY')
            ->call('setChartMetric', 'bookings')
            ->call('openProvisionModal')
            ->assertSet('showProvisionModal', true)
            ->call('closeProvisionModal')
            ->assertSet('showProvisionModal', false)
            ->call('openCreatePlanModal')
            ->assertSet('showPlanModal', false)
            ->call('closePlanModal')
            ->assertOk();
    }
}
