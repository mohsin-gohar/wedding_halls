<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_booking_agreement_and_quotation_require_document_permission_for_tenant_staff(): void
    {
        $booking = Booking::query()->firstOrFail();
        $chef = $this->tenantStaff($booking->tenant_id, 'ROLE_CHEF');
        $manager = $this->tenantStaff($booking->tenant_id, 'ROLE_MANAGER');

        $this->actingAs($chef)
            ->get(route('documents.agreement', ['id' => $booking->id]))
            ->assertForbidden();

        $this->actingAs($chef)
            ->get(route('documents.quotation', ['id' => $booking->id]))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('documents.agreement', ['id' => $booking->id]))
            ->assertOk();
    }

    public function test_invoice_requires_invoice_permission_and_receipt_requires_payment_permission(): void
    {
        $booking = Booking::query()->firstOrFail();
        $payment = Payment::query()
            ->where('tenant_id', $booking->tenant_id)
            ->firstOrFail();

        // Front desk can view/record customer payments but must not see
        // financial invoices unless its role is explicitly granted invoices.view.
        $frontDesk = $this->tenantStaff($booking->tenant_id, 'ROLE_FRONT_DESK');

        $this->actingAs($frontDesk)
            ->get(route('documents.invoice', ['id' => $booking->id]))
            ->assertForbidden();

        $this->actingAs($frontDesk)
            ->get(route('documents.receipt', ['id' => $payment->id]))
            ->assertOk();
    }

    public function test_tenant_staff_without_payment_permission_cannot_read_receipt_or_pdf(): void
    {
        $payment = Payment::query()->firstOrFail();
        $chef = $this->tenantStaff($payment->tenant_id, 'ROLE_CHEF');

        $this->actingAs($chef)
            ->get(route('documents.receipt', ['id' => $payment->id]))
            ->assertForbidden();

        $this->actingAs($chef)
            ->get(route('documents.receipt', ['id' => $payment->id, 'pdf' => 1]))
            ->assertForbidden();
    }

    public function test_unrelated_tenant_staff_cannot_read_booking_documents_or_receipts(): void
    {
        $booking = Booking::query()->firstOrFail();
        $payment = Payment::query()
            ->where('tenant_id', $booking->tenant_id)
            ->firstOrFail();

        $otherTenant = \App\Models\Tenant::query()
            ->where('id', '!=', $booking->tenant_id)
            ->first();

        if (! $otherTenant) {
            $this->markTestSkipped('A second tenant with independent payment fixtures is required.');
        }

        $manager = $this->tenantStaff($otherTenant->id, 'ROLE_MANAGER');

        $this->actingAs($manager)
            ->get(route('documents.agreement', ['id' => $booking->id]))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('documents.receipt', ['id' => $payment->id]))
            ->assertForbidden();
    }

    public function test_platform_finance_can_read_financial_documents_across_tenants(): void
    {
        $booking = Booking::query()->firstOrFail();
        $payment = Payment::query()
            ->where('tenant_id', $booking->tenant_id)
            ->firstOrFail();

        $finance = User::factory()->create([
            'role' => 'ROLE_PLATFORM_FINANCE',
            'tenant_id' => null,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($finance)
            ->get(route('documents.invoice', ['id' => $booking->id]))
            ->assertOk();

        $this->actingAs($finance)
            ->get(route('documents.receipt', ['id' => $payment->id]))
            ->assertOk();
    }

    private function tenantStaff(string|int $tenantId, string $role): User
    {
        return User::factory()->create([
            'tenant_id' => $tenantId,
            'role' => $role,
            'status' => 'ACTIVE',
        ]);
    }
}
