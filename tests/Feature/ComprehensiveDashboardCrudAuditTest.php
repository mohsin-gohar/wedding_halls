<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Livewire\Dashboard;
use App\Livewire\FloorPlanDesigner;
use App\Livewire\HallChat;
use App\Livewire\HallMediaManager;
use App\Livewire\InventoryTracker;
use App\Livewire\PackageManager;
use App\Livewire\StaffAttendance;
use App\Livewire\StaffManager;
use App\Livewire\StaffPayroll;
use App\Livewire\WalkInBookingModal;
use App\Livewire\WhatsAppRsvpManager;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\GuestRsvp;
use App\Models\Hall;
use App\Models\HallChatMessage;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\PlatformTask;
use App\Models\StaffAttendance as StaffAttendanceModel;
use App\Models\StaffPayroll as StaffPayrollModel;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ComprehensiveDashboardCrudAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    /**
     * SUPER ADMIN DASHBOARD CRUD AUDIT
     */
    public function test_super_admin_dashboard_full_crud_operations(): void
    {
        $superAdmin = User::where('role', 'ROLE_SUPER_ADMIN')->firstOrFail();
        $this->actingAs($superAdmin);

        // 1. Platform Tasks CRUD
        $taskComponent = Livewire::test(AdminDashboard::class)
            ->set('newTaskTitle', 'Audit System Security 2026')
            ->set('newTaskCategory', 'SYSTEM_AUDIT')
            ->set('newTaskPriority', 'HIGH')
            ->set('newTaskDueDate', Carbon::tomorrow()->toDateString())
            ->set('newTaskNotes', 'Verify rate limiters and auth guards')
            ->call('addTask')
            ->assertHasNoErrors();

        $createdTask = PlatformTask::where('title', 'Audit System Security 2026')->first();
        $this->assertNotNull($createdTask);
        $this->assertFalse($createdTask->is_completed);

        // Toggle task completion
        $taskComponent->call('toggleTask', (string) $createdTask->id);
        $this->assertTrue($createdTask->fresh()->is_completed);

        // Delete task
        $taskComponent->call('deleteTask', (string) $createdTask->id);
        $this->assertNull(PlatformTask::find($createdTask->id));

        // 2. Vendor Management (KYC Approval & Suspension)
        $tenant = Tenant::firstOrFail();
        $adminComponent = Livewire::test(AdminDashboard::class);

        // Suspend
        $adminComponent->call('suspendVendor', $tenant->id);
        $this->assertEquals('SUSPENDED', $tenant->fresh()->verification_status);

        // Re-approve
        $adminComponent->call('approveVendor', $tenant->id);
        $this->assertEquals('APPROVED', $tenant->fresh()->verification_status);

        // 3. Platform Traffic Switches
        $adminComponent->call('toggleRegistrationStatus');
        $this->assertFalse(cache()->get('platform_vendor_registration_open'));

        $adminComponent->call('toggleRegistrationStatus');
        $this->assertTrue(cache()->get('platform_vendor_registration_open'));

        $adminComponent->set('announcementBanner', 'Special 2026 Ramadan & Eid Packages available now!')
            ->call('saveAnnouncement');
        $this->assertEquals('Special 2026 Ramadan & Eid Packages available now!', cache()->get('platform_announcement'));

        // 4. SaaS Plans CRUD (when billing enabled)
        config(['platform.billing_enabled' => true]);
        $planComponent = Livewire::test(AdminDashboard::class)
            ->call('openCreatePlanModal')
            ->assertSet('showPlanModal', true)
            ->set('planName', 'Platinum Palace SaaS Plan')
            ->set('planSlug', 'platinum-palace-saas-plan')
            ->set('planDescription', 'Exclusive tier for high volume banquet halls')
            ->set('planPriceWeekly', 7500)
            ->set('planPriceMonthly', 29999)
            ->set('planPriceYearly', 299999)
            ->set('planMaxHalls', 5)
            ->set('planMaxStaff', 20)
            ->set('planMaxBookings', 100)
            ->set('planHasKot', true)
            ->set('planHasWhatsapp', true)
            ->set('planHasAccounting', true)
            ->call('savePlan')
            ->assertHasNoErrors();

        $newPlan = SubscriptionPlan::where('slug', 'platinum-palace-saas-plan')->first();
        $this->assertNotNull($newPlan);
        $this->assertEquals('Platinum Palace SaaS Plan', $newPlan->name);

        // Edit Plan
        $planComponent->call('openEditPlanModal', $newPlan->id)
            ->set('planName', 'Platinum Palace Ultra')
            ->call('savePlan')
            ->assertHasNoErrors();

        $this->assertEquals('Platinum Palace Ultra', $newPlan->fresh()->name);

        // Delete Plan
        $planComponent->call('deletePlan', $newPlan->id)
            ->assertHasNoErrors();
        $this->assertNull(SubscriptionPlan::find($newPlan->id));

        // 5. Subscription Payment Clearance
        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => SubscriptionPlan::first()->id,
            'billing_cycle' => 'MONTHLY',
            'amount' => 45000.00,
            'payment_method' => 'BANK_TRANSFER',
            'transaction_reference' => 'TEST-CLEARANCE-101',
            'status' => 'PENDING',
        ]);

        $adminComponent->call('approveSubscriptionPayment', (string) $payment->id);
        $this->assertEquals('APPROVED', $payment->fresh()->status);
        $this->assertEquals($superAdmin->id, $payment->fresh()->approved_by_user_id);
    }

    /**
     * HALL ADMIN (RENT ADMIN) DASHBOARD & ERP CRUD AUDIT
     */
    public function test_hall_admin_dashboard_full_crud_operations(): void
    {
        $owner = User::where('email', 'owner@venuecloud.pk')->firstOrFail();
        $tenantId = $owner->tenant_id;
        $hall = Hall::where('tenant_id', $tenantId)->firstOrFail();
        $this->actingAs($owner);

        // 1. Hall Operations & Daily Kharcha CRUD (Dashboard)
        $dashboardComponent = Livewire::test(Dashboard::class)
            ->call('openDailyExpenseModal', 'GENERATOR_DIESEL')
            ->assertSet('showDailyExpenseModal', true)
            ->set('fuelDieselLiters', 25.0)
            ->set('fuelRatePerLiter', 300.0)
            ->set('expenseDate', Carbon::today()->toDateString())
            ->set('expenseNotes', 'Diesel refill for backup generator')
            ->call('saveDailyExpense')
            ->assertHasNoErrors()
            ->assertSet('showDailyExpenseModal', false);

        $savedDiesel = Expense::where('tenant_id', $tenantId)
            ->where('category', 'GENERATOR_DIESEL')
            ->latest('id')
            ->first();
        $this->assertNotNull($savedDiesel);
        $this->assertEquals(7500.0, (float) $savedDiesel->amount);

        // Delete Daily Expense
        $dashboardComponent->call('deleteDailyExpense', (string) $savedDiesel->id)
            ->assertHasNoErrors();
        $this->assertNull(Expense::find($savedDiesel->id));

        // 2. Walk-in Booking Creation
        $eventDate = Carbon::now()->addDays(20)->toDateString();
        Livewire::test(WalkInBookingModal::class)
            ->call('openModalWithData', ['hall_id' => $hall->id])
            ->assertSet('isOpen', true)
            ->set('event_date', $eventDate)
            ->set('slot_type', 'EVENING')
            ->set('guest_count', 350)
            ->set('customer_name', 'Zubair Farooq')
            ->set('customer_phone', '03001234567')
            ->set('customer_email', 'zubair.farooq@example.com')
            ->set('advance_paid', 50000)
            ->call('confirmBooking')
            ->assertHasNoErrors()
            ->assertSet('step', 5);

        $createdBooking = Booking::where('tenant_id', $tenantId)
            ->where('customer_phone', '03001234567')
            ->first();
        $this->assertNotNull($createdBooking);
        $this->assertEquals('CONFIRMED', $createdBooking->status);
        $this->assertEquals(50000, (float) $createdBooking->advance_paid);

        // 3. Staff Management CRUD (StaffManager)
        $staffComponent = Livewire::test(StaffManager::class)
            ->call('openCreateModal')
            ->assertSet('isCreating', true)
            ->set('name', 'Kamran Akmal')
            ->set('email', 'kamran.staff@venuecloud.pk')
            ->set('phone', '03019876543')
            ->set('role', 'ROLE_FRONT_DESK')
            ->set('baseSalary', 45000)
            ->set('password', 'secret12345')
            ->call('createStaffMember')
            ->assertHasNoErrors();

        $createdStaff = User::where('tenant_id', $tenantId)
            ->where('email', 'kamran.staff@venuecloud.pk')
            ->first();
        $this->assertNotNull($createdStaff);
        $this->assertEquals('ROLE_FRONT_DESK', $createdStaff->role);

        // Edit staff
        $staffComponent->call('openEditModal', $createdStaff->id)
            ->assertSet('isEditing', true)
            ->set('editName', 'Kamran Akmal Updated')
            ->call('updateStaffMember')
            ->assertHasNoErrors();

        $this->assertEquals('Kamran Akmal Updated', $createdStaff->fresh()->name);

        // 4. Staff Attendance Recording
        $attendanceComponent = Livewire::test(StaffAttendance::class)
            ->call('markAllPresent')
            ->call('saveDailyAttendance')
            ->assertHasNoErrors();

        $attendanceRecord = StaffAttendanceModel::where('tenant_id', $tenantId)
            ->where('user_id', $createdStaff->id)
            ->first();
        $this->assertNotNull($attendanceRecord);
        $this->assertEquals('PRESENT', $attendanceRecord->status);

        // 5. Staff Payroll Generation
        Livewire::test(StaffPayroll::class)
            ->call('generateMonthlyPayroll')
            ->assertHasNoErrors();

        $payrollRecord = StaffPayrollModel::where('tenant_id', $tenantId)
            ->where('user_id', $createdStaff->id)
            ->first();
        $this->assertNotNull($payrollRecord);

        // 6. Inventory CRUD (InventoryTracker)
        $inventoryComponent = Livewire::test(InventoryTracker::class)
            ->call('openCreateItemModal')
            ->set('itemName', 'Crystal Water Goblets')
            ->set('itemCategory', 'CROCKERY')
            ->set('itemCurrentStock', 500)
            ->set('itemUnit', 'pieces')
            ->set('itemMinThreshold', 50)
            ->set('itemUnitCost', 150)
            ->call('saveItem')
            ->assertHasNoErrors();

        $item = InventoryItem::where('tenant_id', $tenantId)
            ->where('name', 'Crystal Water Goblets')
            ->first();
        $this->assertNotNull($item);
        $this->assertEquals(500, (int) $item->current_stock);

        // Delete inventory item
        $inventoryComponent->call('deleteItem', $item->id)
            ->assertHasNoErrors();
        $this->assertNull(InventoryItem::find($item->id));

        // 7. Catering Packages CRUD (PackageManager)
        $packageComponent = Livewire::test(PackageManager::class)
            ->call('openCreateModal')
            ->assertSet('showPackageModal', true)
            ->set('packageName', 'Royal Mughal Dastarkhwan')
            ->set('perHeadPrice', 2850)
            ->set('minGuests', 100)
            ->set('packageDescription', 'Authentic traditional wedding banquet with Mutton Biryani and Qorma')
            ->set('newMenuItemName', 'Mutton Degi Biryani')
            ->call('addMenuItem')
            ->call('savePackage')
            ->assertHasNoErrors();

        $createdPackage = Package::where('tenant_id', $tenantId)
            ->where('name', 'Royal Mughal Dastarkhwan')
            ->first();
        $this->assertNotNull($createdPackage);
        $this->assertEquals(2850, (float) $createdPackage->per_head_price);

        // Edit Package
        $packageComponent->call('openEditModal', (string) $createdPackage->id)
            ->set('perHeadPrice', 2950)
            ->call('savePackage')
            ->assertHasNoErrors();

        $this->assertEquals(2950, (float) $createdPackage->fresh()->per_head_price);

        // Delete Package
        $packageComponent->call('deletePackage', (string) $createdPackage->id)
            ->assertHasNoErrors();
        $this->assertNull(Package::find($createdPackage->id));

        // 8. Hall Media & Services Catalog CRUD (HallMediaManager)
        $mediaComponent = Livewire::test(HallMediaManager::class)
            ->set('newServiceTitle', 'Bridal Stage Floral Decor')
            ->set('newServicePrice', 45000)
            ->set('newServiceCategory', 'DECOR')
            ->set('newServiceDescription', 'Imported fresh lilies, red roses, and crystal chandeliers')
            ->set('newServiceComplimentary', false)
            ->call('addService')
            ->assertHasNoErrors()
            ->call('save')
            ->assertHasNoErrors();

        $freshHall = $hall->fresh();
        $this->assertNotEmpty($freshHall->services);
        $titles = collect($freshHall->services)->pluck('title')->toArray();
        $this->assertContains('Bridal Stage Floral Decor', $titles);

        // 9. Seating Planner / Floor Plans Save (FloorPlanDesigner)
        $floorComponent = Livewire::test(FloorPlanDesigner::class)
            ->call('addElement', 'ROUND_TABLE')
            ->call('saveFloorPlan')
            ->assertHasNoErrors();

        // 10. WhatsApp RSVPs (WhatsAppRsvpManager)
        $rsvpComponent = Livewire::test(WhatsAppRsvpManager::class)
            ->set('selectedBookingId', $createdBooking->id)
            ->set('guest_name', 'Nadeem Ahmed')
            ->set('phone', '03215556677')
            ->call('addGuest')
            ->assertHasNoErrors();

        $guest = GuestRsvp::where('tenant_id', $tenantId)
            ->where('phone', '03215556677')
            ->first();
        $this->assertNotNull($guest);
        $this->assertEquals('TENTATIVE', $guest->rsvp_status);

        // Simulate WhatsApp reply
        $rsvpComponent->set('simPhone', '03215556677')
            ->set('simMessage', 'Attending: 4')
            ->call('simulateWhatsAppReply')
            ->assertHasNoErrors();

        $this->assertEquals('ATTENDING', $guest->fresh()->rsvp_status);
        $this->assertEquals(4, $guest->fresh()->attending_count);

        // 11. Real-time Customer & Hall Chat Verification
        $this->post(route('logout'));

        // Guest visitor sends message to hall
        Livewire::test(HallChat::class, ['slug' => $hall->slug])
            ->set('newMessage', 'Salam, do you have dates open in December 2026 for a Walima?')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $message = HallChatMessage::where('hall_id', $hall->id)
            ->where('sender_type', 'customer')
            ->first();
        $this->assertNotNull($message);
        $this->assertStringContainsString('Walima', $message->message);

        // Hall owner logs back in and replies
        $this->actingAs($owner);
        $chatComponent = Livewire::test(HallChat::class, ['slug' => $hall->slug]);
        $chatComponent->call('setSelectedCustomer', $message->ip_address, $message->session_id)
            ->set('newMessage', 'Walaikum Assalam, yes we have open weekend dates. Please visit us!')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $reply = HallChatMessage::where('hall_id', $hall->id)
            ->where('sender_type', 'hall_owner')
            ->first();
        $this->assertNotNull($reply);
        $this->assertStringContainsString('Walaikum Assalam', $reply->message);
    }
}
