<?php

namespace Tests\Feature;

use App\Livewire\CustomerEventWorkspace;
use App\Models\CustomerEvent;
use App\Models\CustomerEventTask;
use App\Models\CustomerEventVendor;
use App\Models\ServiceBooking;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 5 — customer event workspace.
 *
 * Covers event CRUD, checklist/tasks, vendor shortlist, and the booking
 * link/unlink flow that ties direct/RFQ service bookings into an event.
 * Every action is scoped to the signed-in customer — another customer's
 * events and bookings must never be readable or mutable.
 */
class CustomerEventWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'ROLE_CUSTOMER']);
    }

    private function vendor(string $slug): Tenant
    {
        return Tenant::query()->where('slug', $slug)->firstOrFail();
    }

    private function makeEvent(User $owner, array $overrides = []): CustomerEvent
    {
        return CustomerEvent::create(array_merge([
            'user_id' => $owner->id,
            'name' => 'Ali & Fatima Wedding',
            'event_type' => 'WALIMA',
            'event_date' => now()->addDays(40)->toDateString(),
            'city' => 'Lahore',
            'guest_count' => 400,
            'estimated_budget' => 800000,
            'status' => 'PLANNING',
        ], $overrides));
    }

    private function makeBooking(User $customer, Tenant $vendor, array $overrides = []): ServiceBooking
    {
        return ServiceBooking::create(array_merge([
            'tenant_id' => $vendor->id,
            'vendor_category_id' => $vendor->vendor_category_id,
            'customer_id' => $customer->id,
            'booking_reference' => 'RG-SB-EV'.random_int(1000, 9999),
            'event_name' => 'Linked Service',
            'event_date' => now()->addDays(40)->toDateString(),
            'quoted_amount' => 150000,
            'paid_amount' => 25000,
            'status' => 'ACCEPTED',
        ], $overrides));
    }

    // ─────────────────────────────────────────────────────────────
    // Access control
    // ─────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_the_event_workspace(): void
    {
        $this->get(route('customer.events'))->assertRedirect(route('login'));
    }

    public function test_authenticated_customer_can_open_the_workspace(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->get(route('customer.events'))
            ->assertOk()
            ->assertSee('Event Workspace')
            ->assertSee('My Events');
    }

    public function test_customer_dashboard_links_to_the_event_workspace(): void
    {
        $this->actingAs($this->customer())
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee(route('customer.events'), false)
            ->assertSee('My Event Workspace');
    }

    // ─────────────────────────────────────────────────────────────
    // Event CRUD
    // ─────────────────────────────────────────────────────────────

    public function test_customer_can_create_select_and_edit_an_event(): void
    {
        $customer = $this->customer();

        Livewire::actingAs($customer)
            ->test(CustomerEventWorkspace::class)
            ->call('openCreateEventModal')
            ->assertSet('showEventModal', true)
            ->set('eventName', 'Ayesha & Bilal Walima')
            ->set('eventType', 'WALIMA')
            ->set('eventDate', now()->addDays(60)->toDateString())
            ->set('eventCity', 'Karachi')
            ->set('guestCount', 350)
            ->set('estimatedBudget', 900000)
            ->call('saveEvent')
            ->assertHasNoErrors()
            ->assertSet('feedbackMessage', 'Event created — start adding vendors and tasks.');

        $event = CustomerEvent::query()->where('user_id', $customer->id)->firstOrFail();
        $this->assertSame('Ayesha & Bilal Walima', $event->name);
        $this->assertSame('WALIMA', $event->event_type);
        $this->assertSame(350, $event->guest_count);

        Livewire::actingAs($customer)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $event->id)
            ->assertSet('selectedEventId', $event->id)
            ->call('openEditEventModal', $event->id)
            ->set('eventName', 'Ayesha & Bilal Grand Walima')
            ->set('eventStatus', 'BOOKED')
            ->call('saveEvent')
            ->assertHasNoErrors();

        $this->assertSame('Ayesha & Bilal Grand Walima', $event->fresh()->name);
        $this->assertSame('BOOKED', $event->fresh()->status);
    }

    public function test_deleting_an_event_detaches_its_bookings_but_keeps_them(): void
    {
        $customer = $this->customer();
        $event = $this->makeEvent($customer);
        $booking = $this->makeBooking($customer, $this->vendor('nawab-caterers'), [
            'customer_event_id' => $event->id,
        ]);

        Livewire::actingAs($customer)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $event->id)
            ->call('deleteEvent', $event->id)
            ->assertSet('selectedEventId', null);

        $this->assertDatabaseMissing('customer_events', ['id' => $event->id]);
        $this->assertNull($booking->fresh()->customer_event_id, 'Linked booking must survive event deletion.');
    }

    public function test_customer_cannot_select_another_customers_event(): void
    {
        $me = $this->customer();
        $other = $this->customer();
        $theirEvent = $this->makeEvent($other, ['name' => 'Secret Family Event']);

        Livewire::actingAs($me)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $theirEvent->id)
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────
    // Tasks checklist
    // ─────────────────────────────────────────────────────────────

    public function test_customer_can_add_toggle_and_delete_tasks(): void
    {
        $customer = $this->customer();
        $event = $this->makeEvent($customer);

        $component = Livewire::actingAs($customer)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $event->id)
            ->set('taskTitle', 'Confirm menu tasting')
            ->call('addTask')
            ->assertHasNoErrors()
            ->assertSee('Confirm menu tasting');

        $task = CustomerEventTask::query()->where('customer_event_id', $event->id)->firstOrFail();
        $this->assertSame('PENDING', $task->status);

        $component->call('toggleTask', $task->id);
        $this->assertSame('DONE', $task->fresh()->status);

        $component->call('toggleTask', $task->id);
        $this->assertSame('PENDING', $task->fresh()->status);

        $component->call('deleteTask', $task->id);
        $this->assertDatabaseMissing('customer_event_tasks', ['id' => $task->id]);
    }

    // ─────────────────────────────────────────────────────────────
    // Vendor shortlist
    // ─────────────────────────────────────────────────────────────

    public function test_expired_vendor_is_excluded_from_marketplace_shortlist_and_cannot_be_attached(): void
    {
        $customer = $this->customer();
        $event = $this->makeEvent($customer);
        $vendor = $this->vendor('nawab-caterers');
        $vendor->update([
            'subscription_status' => 'EXPIRED',
            'subscription_expires_at' => now()->addYear(),
        ]);

        $component = Livewire::actingAs($customer)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $event->id);

        $visibleVendors = $component->get('marketplaceVendors');
        $this->assertFalse($visibleVendors->contains('id', $vendor->id));

        $component->call('attachVendor', (string) $vendor->id)
            ->assertSet('errorMessage', 'That vendor is not available for shortlisting.');

        $this->assertDatabaseMissing('customer_event_vendors', [
            'customer_event_id' => $event->id,
            'tenant_id' => $vendor->id,
        ]);
    }

    public function test_customer_can_shortlist_update_and_detach_a_vendor(): void
    {
        $customer = $this->customer();
        $event = $this->makeEvent($customer);
        $vendor = $this->vendor('nawab-caterers');

        $component = Livewire::actingAs($customer)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $event->id)
            ->call('attachVendor', $vendor->id)
            ->assertSet('feedbackMessage', $vendor->name.' added to the shortlist.');

        $row = CustomerEventVendor::query()
            ->where('customer_event_id', $event->id)
            ->where('tenant_id', $vendor->id)
            ->firstOrFail();

        $this->assertSame('SHORTLISTED', $row->status);

        $component->call('updateVendorStatus', $row->id, 'REQUESTED');
        $this->assertSame('REQUESTED', $row->fresh()->status);

        $component->call('detachVendor', $row->id);
        $this->assertDatabaseMissing('customer_event_vendors', ['id' => $row->id]);
    }

    // ─────────────────────────────────────────────────────────────
    // Booking link / unlink
    // ─────────────────────────────────────────────────────────────

    public function test_customer_can_link_and_unlink_their_own_booking(): void
    {
        $customer = $this->customer();
        $event = $this->makeEvent($customer);
        $vendor = $this->vendor('nawab-caterers');
        $booking = $this->makeBooking($customer, $vendor, ['booking_reference' => 'RG-SB-LINK01']);

        $component = Livewire::actingAs($customer)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $event->id)
            ->assertSee($booking->booking_reference) // in unlinked list
            ->call('linkBooking', $booking->id)
            ->assertHasNoErrors();

        $this->assertSame($event->id, $booking->fresh()->customer_event_id);
        $component->assertSee($booking->booking_reference);

        $component->call('unlinkBooking', $booking->id)
            ->assertHasNoErrors();

        $this->assertNull($booking->fresh()->customer_event_id);
    }

    public function test_linking_a_booking_marks_the_matching_shortlisted_vendor_as_booked(): void
    {
        $customer = $this->customer();
        $event = $this->makeEvent($customer);
        $vendor = $this->vendor('lens-and-lantern-studio');
        $booking = $this->makeBooking($customer, $vendor);

        $row = CustomerEventVendor::create([
            'customer_event_id' => $event->id,
            'tenant_id' => $vendor->id,
            'status' => 'SHORTLISTED',
        ]);

        Livewire::actingAs($customer)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $event->id)
            ->call('linkBooking', $booking->id);

        $row->refresh();
        $this->assertSame('BOOKED', $row->status);
        $this->assertSame($booking->id, $row->service_booking_id);

        // Unlinking clears the booking reference but keeps the shortlist row.
        Livewire::actingAs($customer)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $event->id)
            ->call('unlinkBooking', $booking->id);

        $row->refresh();
        $this->assertNull($row->service_booking_id);
    }

    public function test_customer_cannot_link_another_customers_booking(): void
    {
        $me = $this->customer();
        $other = $this->customer();
        $event = $this->makeEvent($me);
        $theirBooking = $this->makeBooking($other, $this->vendor('nawab-caterers'));

        Livewire::actingAs($me)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $event->id)
            ->call('linkBooking', $theirBooking->id)
            ->assertStatus(404);

        $this->assertNull($theirBooking->fresh()->customer_event_id);
    }

    public function test_budget_reflects_linked_bookings(): void
    {
        $customer = $this->customer();
        $event = $this->makeEvent($customer);
        $vendor = $this->vendor('nawab-caterers');

        $this->makeBooking($customer, $vendor, [
            'customer_event_id' => $event->id,
            'quoted_amount' => 200000,
            'paid_amount' => 50000,
        ]);
        $this->makeBooking($customer, $vendor, [
            'customer_event_id' => $event->id,
            'quoted_amount' => 100000,
            'paid_amount' => 10000,
        ]);

        $budget = Livewire::actingAs($customer)
            ->test(CustomerEventWorkspace::class)
            ->call('selectEvent', $event->id)
            ->get('eventBudget');

        $this->assertSame(300000.0, (float) $budget['quoted']);
        $this->assertSame(60000.0, (float) $budget['paid']);
        $this->assertSame(240000.0, (float) $budget['outstanding']);
        $this->assertSame(2, $budget['booking_count']);
    }
}
