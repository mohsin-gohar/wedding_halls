<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Livewire\CustomerEventWorkspace;
use App\Livewire\CustomerLogin;
use App\Livewire\HallReviews;
use App\Livewire\NotificationCenter;
use App\Livewire\SupportCenter;
use App\Livewire\WhiteLabelSite;
use App\Models\Booking;
use App\Models\CustomerEvent;
use App\Models\CustomerEventTask;
use App\Models\CustomerEventVendor;
use App\Models\Hall;
use App\Models\Notification;
use App\Models\Review;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\Venue;
use App\Services\SubscriptionService;
use App\Support\TenantBrand;
use Database\Seeders\PermissionMatrixSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CustomerSaasHardeningRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionMatrixSeeder::class);
    }

    private function customer(string $email = 'customer-a@example.com'): User
    {
        return User::create([
            'name' => 'Test Customer',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => 'ROLE_CUSTOMER',
            'status' => 'ACTIVE',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Platform Director',
            'email' => 'saas-admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'ROLE_SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);
    }

    private function tenant(string $slug, array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => 'Test Vendor '.$slug,
            'slug' => $slug,
            'subdomain' => $slug,
            'email' => $slug.'@example.com',
            'phone' => '+92 300 0000000',
            'verification_status' => 'APPROVED',
            'subscription_status' => 'ACTIVE',
            'is_active' => true,
            'marketplace_visible' => true,
            'subscription_expires_at' => now()->addYear(),
        ], $overrides));
    }

    private function hall(Tenant $tenant): Hall
    {
        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Venue '.$tenant->slug,
            'slug' => 'venue-'.$tenant->slug,
            'city' => 'Lahore',
            'address' => 'Main Boulevard',
            'is_active' => true,
        ]);

        return Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Hall '.$tenant->slug,
            'slug' => 'hall-'.$tenant->slug,
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 100000,
            'is_active' => true,
        ]);
    }

    public function test_event_tasks_and_vendors_are_never_read_from_a_tampered_event_id(): void
    {
        $owner = $this->customer();
        $victim = $this->customer('customer-b@example.com');
        $victimTenant = $this->tenant('victim-vendor');

        $victimEvent = CustomerEvent::create([
            'user_id' => $victim->id,
            'name' => 'Victim Walima',
            'event_type' => 'WEDDING',
            'status' => 'PLANNING',
        ]);

        CustomerEventTask::create([
            'customer_event_id' => $victimEvent->id,
            'title' => 'Secret vendor meeting',
            'status' => 'PENDING',
            'sort' => 1,
        ]);
        CustomerEventVendor::create([
            'customer_event_id' => $victimEvent->id,
            'tenant_id' => $victimTenant->id,
            'status' => 'SHORTLISTED',
        ]);

        $this->actingAs($owner);

        $component = Livewire::test(CustomerEventWorkspace::class)
            ->set('selectedEventId', $victimEvent->id);

        $this->assertCount(0, $component->instance()->eventTasks);
        $this->assertCount(0, $component->instance()->eventVendors);
        $this->assertNull($component->instance()->selectedEvent);
    }

    public function test_attach_vendor_requires_marketplace_visibility(): void
    {
        $owner = $this->customer();
        $event = CustomerEvent::create([
            'user_id' => $owner->id,
            'name' => 'My Mehndi',
            'event_type' => 'MEHNDI',
            'status' => 'PLANNING',
        ]);
        $hiddenVendor = $this->tenant('hidden-vendor', ['marketplace_visible' => false]);

        $this->actingAs($owner);

        Livewire::test(CustomerEventWorkspace::class)
            ->set('selectedEventId', $event->id)
            ->call('attachVendor', $hiddenVendor->id)
            ->assertSet('errorMessage', 'That vendor is not available for shortlisting.');

        $this->assertDatabaseCount('customer_event_vendors', 0);
    }

    public function test_support_messages_ignore_tampered_selected_ticket_id(): void
    {
        $requester = $this->customer();
        $victim = $this->customer('support-victim@example.com');

        $victimTicket = SupportTicket::create([
            'ticket_no' => SupportTicket::generateTicketNo(),
            'user_id' => $victim->id,
            'subject' => 'Private complaint',
            'category' => 'GENERAL',
            'priority' => 'MEDIUM',
            'status' => 'OPEN',
        ]);

        TicketMessage::create([
            'support_ticket_id' => $victimTicket->id,
            'user_id' => $victim->id,
            'message' => 'This message belongs to another customer.',
            'is_staff_reply' => false,
        ]);

        $this->actingAs($requester);

        $component = Livewire::test(SupportCenter::class)
            ->set('selectedTicketId', $victimTicket->id);

        $this->assertCount(0, $component->instance()->messages);
        $this->assertNull($component->instance()->selectedTicket);
    }

    public function test_closed_ticket_rejects_reply_and_staff_reply_sets_read_state_and_notification(): void
    {
        $customer = $this->customer();
        $vendor = $this->tenant('reply-vendor');
        $staff = User::create([
            'name' => 'Vendor Staff',
            'email' => 'staff@reply-vendor.example.com',
            'password' => Hash::make('password123'),
            'role' => 'ROLE_TENANT_OWNER',
            'status' => 'ACTIVE',
            'tenant_id' => $vendor->id,
        ]);

        $ticket = SupportTicket::create([
            'ticket_no' => SupportTicket::generateTicketNo(),
            'user_id' => $customer->id,
            'tenant_id' => $vendor->id,
            'subject' => 'Payment not reflected',
            'category' => 'PAYMENT',
            'priority' => 'HIGH',
            'status' => 'OPEN',
        ]);

        $original = TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'message' => 'Please check my_cash payment receipt.',
            'is_staff_reply' => false,
        ]);

        $this->actingAs($staff);

        Livewire::test(SupportCenter::class)
            ->set('selectedTicketId', $ticket->id)
            ->set('replyBody', 'We have verified the cash receipt.')
            ->call('reply');

        $this->assertNotNull($original->fresh()->read_at);
        $this->assertDatabaseHas('ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $staff->id,
            'read_at' => null,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'type' => 'SUPPORT_REPLY',
        ]);

        $ticket->forceFill(['status' => 'CLOSED'])->save();
        $messageCount = TicketMessage::query()->where('support_ticket_id', $ticket->id)->count();

        Livewire::test(SupportCenter::class)
            ->set('selectedTicketId', $ticket->id)
            ->set('replyBody', 'One more reply')
            ->call('reply')
            ->assertSet('errorMessage', 'This ticket is closed and no longer accepts replies.');

        $this->assertSame($messageCount, TicketMessage::query()->where('support_ticket_id', $ticket->id)->count());
    }

    public function test_customer_login_rejects_suspended_inactive_and_blocked_accounts(): void
    {
        foreach (['SUSPENDED', 'INACTIVE', 'BLOCKED'] as $status) {
            $user = User::create([
                'name' => 'Blocked '.$status,
                'email' => strtolower($status).'-login@example.com',
                'password' => Hash::make('password123'),
                'role' => 'ROLE_CUSTOMER',
                'status' => $status,
            ]);

            Livewire::test(CustomerLogin::class)
                ->set('email', $user->email)
                ->set('password', 'password123')
                ->call('login')
                ->assertHasErrors('email');

            $this->assertFalse(Auth::check());
            $this->assertNull($user->fresh()->last_login_at);
        }
    }

    public function test_billing_actions_are_blocked_by_direct_calls_when_billing_is_disabled(): void
    {
        config(['platform.billing_enabled' => false]);

        $admin = $this->admin();
        $tenant = $this->tenant('billing-tenant');
        $plan = SubscriptionPlan::create([
            'name' => 'Billing Plan',
            'slug' => 'billing-plan',
            'price_monthly' => 1000,
            'price_yearly' => 10000,
            'max_halls' => 1,
            'max_staff' => 1,
            'max_monthly_bookings' => 5,
            'is_active' => true,
        ]);
        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'billing_cycle' => 'MONTHLY',
            'amount' => 1000,
            'payment_method' => 'CASH',
            'transaction_reference' => 'CASH-RECEIPT-001',
            'status' => 'PENDING',
        ]);

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('planName', 'Unauthorized Plan')
            ->call('savePlan')
            ->call('assignPlanToTenant', $tenant->id, $plan->id)
            ->call('approveSubscriptionPayment', $payment->id)
            ->call('rejectSubscriptionPayment', $payment->id)
            ->call('deletePlan', $plan->id);

        $this->assertDatabaseMissing('subscription_plans', ['name' => 'Unauthorized Plan']);
        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id]);
        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertNull($tenant->fresh()->subscription_plan_id);
    }

    public function test_admin_suspension_sets_the_state_recognised_by_the_lock_predicate(): void
    {
        $tenant = $this->tenant('suspend-me');

        $this->actingAs($this->admin());

        Livewire::test(AdminDashboard::class)
            ->call('suspendVendor', $tenant->id);

        $fresh = $tenant->fresh();
        $this->assertSame('SUSPENDED', $fresh->subscription_status);
        $this->assertTrue(app(SubscriptionService::class)->isLocked($fresh));

        // White-label access is middleware-free and must honour the lock.
        $component = app(WhiteLabelSite::class);
        $component->slug = $fresh->slug;
        $this->assertNull($component->tenant);
    }

    public function test_site_url_points_at_the_existing_microsite_route(): void
    {
        $tenant = $this->tenant('site-url-vendor');

        $url = TenantBrand::for($tenant)->siteUrl();

        $this->assertStringContainsString('/site/'.$tenant->slug, $url);
    }

    public function test_review_moderation_requires_permission_and_records_verified_booking(): void
    {
        $tenant = $this->tenant('review-vendor');
        $hall = $this->hall($tenant);
        $customer = $this->customer('reviewer@example.com');

        Booking::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hall->id,
            'customer_id' => $customer->id,
            'booking_reference' => 'BK-REVIEW-001',
            'customer_name' => $customer->name,
            'customer_phone' => '+92 300 0000000',
            'event_date' => now()->addMonth()->toDateString(),
            'status' => 'CONFIRMED',
        ]);

        $review = Review::create([
            'user_id' => $customer->id,
            'hall_id' => $hall->id,
            'rating' => 5,
            'title' => 'Beautiful night',
            'body' => 'The decor and service were outstanding throughout the event.',
            'status' => Review::STATUS_PENDING,
        ]);

        // A customer without reviews.moderate cannot drive moderation.
        $this->actingAs($customer);
        $unauthorised = app(HallReviews::class);
        $unauthorised->mount($hall->id);

        try {
            $unauthorised->approveReview($review->id);
            $this->fail('A customer must not be able to moderate reviews.');
        } catch (HttpException) {
            // Expected.
        }

        $this->assertSame(Review::STATUS_PENDING, $review->fresh()->status);

        $moderator = User::create([
            'name' => 'Content Moderator',
            'email' => 'moderator@example.com',
            'password' => Hash::make('password123'),
            'role' => 'ROLE_CONTENT_MODERATOR',
            'status' => 'ACTIVE',
        ]);
        $moderator->assignRole('ROLE_CONTENT_MODERATOR');

        $this->actingAs($moderator);

        Livewire::test(HallReviews::class, ['hallId' => $hall->id])
            ->call('approveReview', $review->id);

        $approved = $review->fresh();
        $this->assertSame(Review::STATUS_APPROVED, $approved->status);
        $this->assertSame($moderator->id, $approved->moderated_by);
        $this->assertNotNull($approved->moderated_at);
        $this->assertTrue((bool) $approved->is_verified_booking);
        $this->assertNotNull($approved->booking_id);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'type' => 'REVIEW_MODERATED',
        ]);
        $this->assertDatabaseCount('notifications', 1);

        // NotificationCenter still scopes reads to the signed-in recipient.
        $other = $this->customer('other-notifications@example.com');
        $this->actingAs($other);
        $centre = Livewire::test(NotificationCenter::class);
        $this->assertCount(0, $centre->instance()->render()->getData()['notifications']);
        $this->assertGreaterThanOrEqual(1, Notification::query()->count());
    }
}
