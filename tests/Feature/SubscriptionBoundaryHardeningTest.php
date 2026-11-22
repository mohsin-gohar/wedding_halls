<?php

namespace Tests\Feature;

use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Subscription renewal is a financial write: the plan owns the price and only
 * a Super Admin may instantly activate a tenant.
 */
class SubscriptionBoundaryHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function tenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Boundary Banquets',
            'slug' => 'boundary-banquets-'.uniqid(),
            'email' => 'boundary@example.pk',
            'verification_status' => 'APPROVED',
            'subscription_status' => 'EXPIRED',
            'subscription_expires_at' => Carbon::now()->subDay(),
            'is_active' => false,
        ]);
    }

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Boundary Plan',
            'slug' => 'boundary-plan-'.uniqid(),
            'price_weekly' => 6000,
            'price_monthly' => 20000,
            'price_yearly' => 200000,
            'max_halls' => 2,
            'max_staff' => 5,
            'max_monthly_bookings' => 20,
        ]);
    }

    public function test_tampered_subscription_amount_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must match the selected plan price');

        app(SubscriptionService::class)->renewSubscription(
            $this->tenant(),
            $this->plan(),
            'MONTHLY',
            1,
            'CASH',
            'TAMPERED-REF'
        );
    }

    public function test_instant_approval_requires_an_approver_identity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('approving Super Admin');

        app(SubscriptionService::class)->renewSubscription(
            $this->tenant(),
            $this->plan(),
            'MONTHLY',
            20000,
            'CASH',
            'NO-APPROVER-REF',
            true
        );
    }

    public function test_non_super_admin_cannot_instantly_approve_a_subscription(): void
    {
        $staff = User::factory()->create([
            'role' => 'ROLE_TENANT_OWNER',
            'status' => 'ACTIVE',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only a Super Admin');

        app(SubscriptionService::class)->renewSubscription(
            $this->tenant(),
            $this->plan(),
            'MONTHLY',
            20000,
            'CASH',
            'BAD-APPROVER-REF',
            true,
            (int) $staff->id
        );
    }

    public function test_pending_cash_submission_does_not_activate_the_tenant(): void
    {
        $tenant = $this->tenant();

        $payment = app(SubscriptionService::class)->renewSubscription(
            $tenant,
            $this->plan(),
            'MONTHLY',
            20000,
            'CASH',
            'PENDING-SUBMISSION-REF'
        );

        $this->assertSame('PENDING', $payment->status);
        $this->assertNull($payment->approved_by_user_id);
        $this->assertSame('EXPIRED', $tenant->fresh()->subscription_status);
        $this->assertSame(1, SubscriptionPayment::query()->where('transaction_reference', 'PENDING-SUBMISSION-REF')->count());
    }
}
