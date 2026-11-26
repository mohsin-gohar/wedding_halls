<?php

namespace Tests\Feature;

use App\Models\Booking;
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
        $payroll = \App\Models\StaffPayroll::query()->first();

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
}
