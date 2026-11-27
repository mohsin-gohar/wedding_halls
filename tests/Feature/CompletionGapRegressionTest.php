<?php

namespace Tests\Feature;

use App\Livewire\CalendarShiftGrid;
use App\Livewire\ExpenseTracker;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\Hall;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompletionGapRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function tenantAndOwner(): array
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $owner = User::query()->where('tenant_id', $tenant->id)->where('role', 'ROLE_TENANT_OWNER')->firstOrFail();

        return [$tenant, $owner];
    }

    private function cancelledBooking(Tenant $tenant): Booking
    {
        $venue = Venue::create([
            'tenant_id' => $tenant->id, 'name' => 'Refund Venue', 'slug' => 'refund-venue-'.$tenant->id,
            'city' => 'Lahore', 'is_active' => true,
        ]);
        $hall = Hall::create([
            'tenant_id' => $tenant->id, 'venue_id' => $venue->id, 'name' => 'Refund Hall',
            'slug' => 'refund-hall-'.$tenant->id, 'min_capacity' => 50, 'max_capacity' => 500,
            'base_rent' => 100000, 'is_active' => true,
        ]);

        return Booking::create([
            'tenant_id' => $tenant->id, 'hall_id' => $hall->id,
            'customer_name' => 'Refund Host', 'customer_phone' => '+92 300 2222222',
            'event_date' => now()->addDays(30)->toDateString(), 'slot_type' => 'EVENING',
            'guest_count' => 200, 'status' => 'CANCELLED', 'advance_paid' => 5000,
            'balance_due' => 95000, 'grand_total' => 100000,
        ]);
    }

    public function test_authorized_owner_can_record_cash_refund_from_calendar_ui(): void
    {
        [$tenant, $owner] = $this->tenantAndOwner();
        $booking = $this->cancelledBooking($tenant);

        Livewire::actingAs($owner)
            ->test(CalendarShiftGrid::class)
            ->call('openRefundModal', $booking->id)
            ->assertSet('showRefundModal', true)
            ->set('refundAmount', 2000)
            ->set('refundReference', 'REF-UI-TEST-001')
            ->call('recordBookingRefund')
            ->assertSet('showRefundModal', false);

        $refund = Payment::query()->where('booking_id', $booking->id)->where('payment_type', 'REFUND')->firstOrFail();
        $this->assertSame('CASH', $refund->payment_method);
        $this->assertSame(2000.0, (float) $refund->amount);
        $this->assertSame(3000.0, (float) $booking->fresh()->advance_paid);
    }

    public function test_unauthorized_staff_cannot_record_refund_and_over_refund_is_rejected(): void
    {
        [$tenant, $owner] = $this->tenantAndOwner();
        $booking = $this->cancelledBooking($tenant);
        $frontDesk = User::factory()->create([
            'tenant_id' => $tenant->id, 'role' => 'ROLE_FRONT_DESK', 'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($frontDesk)
            ->test(CalendarShiftGrid::class)
            ->call('recordBookingRefund')
            ->assertStatus(403);

        Livewire::actingAs($owner)
            ->test(CalendarShiftGrid::class)
            ->call('openRefundModal', $booking->id)
            ->set('refundAmount', 6000)
            ->set('refundReference', 'REF-UI-OVER-001')
            ->call('recordBookingRefund')
            ->assertHasErrors('refundAmount');

        $this->assertSame(0, Payment::query()->where('booking_id', $booking->id)->where('payment_type', 'REFUND')->count());
    }

    public function test_source_linked_expense_correction_preserves_original_and_appends_delta(): void
    {
        [$tenant, $owner] = $this->tenantAndOwner();
        $expense = Expense::create([
            'tenant_id' => $tenant->id, 'category' => 'GENERATOR_DIESEL',
            'title' => 'Posted fuel expense', 'amount' => 2000,
            'expense_date' => now()->toDateString(), 'payment_method' => 'CASH',
            'created_by_user_id' => $owner->id,
        ]);

        Livewire::actingAs($owner)
            ->test(ExpenseTracker::class)
            ->call('openCorrectionModal', $expense->id)
            ->set('correctionDesiredAmount', '1500')
            ->set('correctionReason', 'Fuel quantity was over-recorded')
            ->call('correctExpense')
            ->assertHasNoErrors();

        $this->assertSame(2000.0, (float) $expense->fresh()->amount);
        $this->assertFalse((bool) $expense->fresh()->is_voided);
        $correction = $expense->corrections()->firstOrFail();
        $this->assertSame(-500.0, (float) $correction->amount);
        $this->assertSame($expense->id, $correction->corrects_expense_id);
        $this->assertSame(1, (int) $correction->correction_sequence);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id, 'action' => 'EXPENSE_CORRECTED', 'entity_id' => $correction->id,
        ]);
    }
}
