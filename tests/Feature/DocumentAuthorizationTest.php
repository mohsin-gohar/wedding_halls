<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\KitchenKot;
use App\Models\Payment;
use App\Models\StaffPayroll;
use App\Models\Tenant;
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

    public function test_operational_staff_cannot_open_contract_or_invoice_documents_without_permissions(): void
    {
        $booking = Booking::query()->firstOrFail();
        $chef = User::factory()->create([
            'tenant_id' => $booking->tenant_id,
            'role' => 'ROLE_CHEF',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($chef)
            ->get(route('documents.agreement', $booking->id))
            ->assertForbidden();

        $this->actingAs($chef)
            ->get(route('documents.invoice', $booking->id))
            ->assertForbidden();

        $this->actingAs($chef)
            ->get(route('documents.quotation', $booking->id))
            ->assertForbidden();
    }

    public function test_tenant_owner_can_open_authorized_financial_and_contract_documents(): void
    {
        $booking = Booking::query()->firstOrFail();
        $owner = User::query()
            ->where('tenant_id', $booking->tenant_id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();

        $this->actingAs($owner)
            ->get(route('documents.agreement', $booking->id))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('documents.invoice', $booking->id))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('documents.quotation', $booking->id))
            ->assertOk();
    }

    public function test_salary_slip_access_requires_payroll_permission_or_employee_ownership(): void
    {
        $payroll = StaffPayroll::query()->first();

        if (! $payroll) {
            $this->markTestSkipped('The database seeder does not create payroll records.');
        }

        $manager = User::factory()->create([
            'tenant_id' => $payroll->tenant_id,
            'role' => 'ROLE_MANAGER',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($manager)
            ->get(route('documents.salary-slip', $payroll->id))
            ->assertForbidden();
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

        $otherTenant = Tenant::query()
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

    public function test_kitchen_documents_require_the_kitchen_view_permission(): void
    {
        $kot = KitchenKot::query()->firstOrFail();
        $chef = $this->tenantStaff($kot->tenant_id, 'ROLE_CHEF');
        $accountant = $this->tenantStaff($kot->tenant_id, 'ROLE_ACCOUNTANT');

        $this->actingAs($chef)
            ->get(route('documents.kot', ['id' => $kot->id]))
            ->assertOk();

        $this->actingAs($accountant)
            ->get(route('documents.kot', ['id' => $kot->id]))
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

    public function test_salary_slip_requires_payroll_permission_except_for_the_employee(): void
    {
        $booking = Booking::query()->firstOrFail();
        $employee = $this->tenantStaff($booking->tenant_id, 'ROLE_CHEF');
        $manager = $this->tenantStaff($booking->tenant_id, 'ROLE_MANAGER');
        $accountant = $this->tenantStaff($booking->tenant_id, 'ROLE_ACCOUNTANT');

        $payroll = StaffPayroll::create([
            'tenant_id' => $booking->tenant_id,
            'user_id' => $employee->id,
            'month_year' => now()->format('Y-m'),
            'basic_salary' => 30000,
            'net_salary' => 30000,
            'status' => 'PAID',
            'payment_method' => 'CASH',
        ]);

        $this->actingAs($manager)
            ->get(route('documents.salary-slip', ['id' => $payroll->id]))
            ->assertForbidden();

        $this->actingAs($accountant)
            ->get(route('documents.salary-slip', ['id' => $payroll->id]))
            ->assertOk();

        $this->actingAs($employee)
            ->get(route('documents.salary-slip', ['id' => $payroll->id]))
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
