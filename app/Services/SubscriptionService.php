<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CashOnlyPayments;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public const GRACE_PERIOD_DAYS = 3;

    /**
     * Inspect all active/trial tenants and expire any accounts whose subscription + grace period has lapsed.
     * Crucial Guarantee: Data is NEVER deleted on expiry; historical records remain strictly intact.
     */
    public function checkAndExpireSubscriptions(): array
    {
        $now = Carbon::now();
        $expiredCount = 0;
        $warningCount = 0;
        $processedTenants = [];

        $tenants = Tenant::whereIn('subscription_status', ['ACTIVE', 'TRIAL', 'GRACE_PERIOD'])
            ->whereNotNull('subscription_expires_at')
            ->get();

        foreach ($tenants as $tenant) {
            $expiresAt = $tenant->subscription_expires_at;
            $graceEndsAt = $expiresAt->copy()->addDays(self::GRACE_PERIOD_DAYS);

            if ($now->greaterThan($graceEndsAt)) {
                // Subscription has fully expired past grace period
                $previousStatus = $tenant->subscription_status;
                $tenant->update([
                    'subscription_status' => 'EXPIRED',
                ]);

                AuditLog::log(
                    'SUBSCRIPTION_EXPIRED',
                    "Tenant [{$tenant->name}] transitioned from [{$previousStatus}] to [EXPIRED]. Access restricted to renewal portal. All historical hall and booking data preserved.",
                    $tenant->id,
                    'Tenant',
                    (string) $tenant->id
                );

                $expiredCount++;
                $processedTenants[] = [
                    'tenant_id' => $tenant->id,
                    'name' => $tenant->name,
                    'action' => 'EXPIRED',
                    'expired_at' => $expiresAt->toIso8601String(),
                ];
            } elseif ($now->greaterThan($expiresAt) && $tenant->subscription_status !== 'GRACE_PERIOD') {
                // Within grace period (0 - 3 days past expiry)
                $tenant->update([
                    'subscription_status' => 'GRACE_PERIOD',
                ]);

                AuditLog::log(
                    'SUBSCRIPTION_GRACE_PERIOD',
                    "Tenant [{$tenant->name}] entered 3-day grace period. Final expiry will occur on [{$graceEndsAt->format('Y-m-d')}].",
                    $tenant->id,
                    'Tenant',
                    (string) $tenant->id
                );

                $warningCount++;
                $processedTenants[] = [
                    'tenant_id' => $tenant->id,
                    'name' => $tenant->name,
                    'action' => 'GRACE_PERIOD',
                    'grace_ends_at' => $graceEndsAt->toIso8601String(),
                ];
            }
        }

        return [
            'expired_count' => $expiredCount,
            'warning_count' => $warningCount,
            'details' => $processedTenants,
        ];
    }

    /**
     * Renew or activate a tenant subscription with the specified plan and cycle.
     */
    public function renewSubscription(
        Tenant $tenant,
        SubscriptionPlan $plan,
        string $billingCycle = 'MONTHLY',
        ?float $amount = null,
        string $paymentMethod = CashOnlyPayments::CASH,
        ?string $transactionReference = null,
        bool $autoApprove = false,
        ?int $approvedByUserId = null
    ): SubscriptionPayment {
        CashOnlyPayments::assert($paymentMethod, 'subscription payments');

        $billingCycle = strtoupper($billingCycle);
        if (! in_array($billingCycle, ['WEEKLY', 'MONTHLY', 'YEARLY'], true)) {
            throw new \InvalidArgumentException('Unsupported subscription billing cycle.');
        }

        // The plan is the only price authority. A caller may omit the amount, but
        // supplying a different one must never activate a subscription cheaply.
        $planAmount = round((float) match ($billingCycle) {
            'WEEKLY' => $plan->price_weekly ?? 0,
            'YEARLY' => $plan->price_yearly ?? 0,
            default => $plan->price_monthly ?? 0,
        }, 2);

        if ($planAmount <= 0) {
            throw new \InvalidArgumentException('The selected subscription plan has no valid price for this cycle.');
        }

        if ($amount !== null && abs(round($amount, 2) - $planAmount) > 0.009) {
            throw new \InvalidArgumentException('Subscription amount must match the selected plan price.');
        }

        $amount = $planAmount;

        // Instant activation is a privileged act. It requires an authenticated
        // Super Admin identity at the service boundary, not merely a boolean flag.
        if ($autoApprove) {
            if (! $approvedByUserId) {
                throw new \InvalidArgumentException('Instant subscription approval requires an approving Super Admin.');
            }

            $approver = User::query()->findOrFail($approvedByUserId);

            if (! $approver->isSuperAdmin()) {
                throw new \InvalidArgumentException('Only a Super Admin may instantly approve a subscription.');
            }
        }

        return DB::transaction(function () use (
            $tenant,
            $plan,
            $billingCycle,
            $amount,
            $paymentMethod,
            $transactionReference,
            $autoApprove,
            $approvedByUserId
        ): SubscriptionPayment {
            $payment = SubscriptionPayment::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'transaction_reference' => $transactionReference ?? ('RG-PAY-'.strtoupper(bin2hex(random_bytes(4)))),
                'status' => $autoApprove ? 'APPROVED' : 'PENDING',
                'approved_by_user_id' => $autoApprove ? $approvedByUserId : null,
                'approved_at' => $autoApprove ? Carbon::now() : null,
                'notes' => $autoApprove
                    ? 'Instant renewal activated by Super Admin.'
                    : 'Manual cash payment submitted; pending Super Admin verification.',
            ]);

            if ($autoApprove) {
                $this->applyApprovedSubscription($tenant->fresh(), $plan, $billingCycle);
            }

            return $payment;
        });
    }

    /**
     * Apply active subscription dates to tenant upon payment verification.
     */
    public function applyApprovedSubscription(Tenant $tenant, SubscriptionPlan $plan, string $billingCycle): void
    {
        $now = Carbon::now();
        // If current subscription is still active and not expired, extend from existing expiry date
        $baseDate = ($tenant->subscription_expires_at && $tenant->subscription_expires_at->isFuture())
            ? $tenant->subscription_expires_at
            : $now;

        $newExpiresAt = match (strtoupper($billingCycle)) {
            'WEEKLY' => $baseDate->copy()->addDays(7),
            'YEARLY' => $baseDate->copy()->addYear(),
            default => $baseDate->copy()->addMonth(),
        };

        $tenant->update([
            'subscription_plan_id' => $plan->id,
            'subscription_plan' => $plan->name,
            'billing_cycle' => strtoupper($billingCycle),
            'subscription_status' => 'ACTIVE',
            'subscription_starts_at' => $now,
            'subscription_expires_at' => $newExpiresAt,
            'is_active' => true,
        ]);

        AuditLog::log(
            'SUBSCRIPTION_RENEWED',
            "Tenant [{$tenant->name}] subscription renewed for [{$plan->name}] on [{$billingCycle}] cycle until [{$newExpiresAt->format('Y-m-d H:i')}].",
            $tenant->id,
            'Tenant',
            (string) $tenant->id
        );
    }

    /**
     * Determine if a tenant is locked out due to subscription expiration.
     */
    public function isLocked(Tenant $tenant): bool
    {
        $status = strtoupper((string) $tenant->subscription_status);

        // A platform/vendor suspension is a lock regardless of dates, and an
        // expired or cancelled subscription is never entitled to access.
        if (in_array($status, ['SUSPENDED', 'EXPIRED', 'CANCELLED'], true)) {
            return true;
        }

        $expiresAt = $tenant->subscription_expires_at;

        if ($expiresAt && $expiresAt->isPast()) {
            $graceEnds = $expiresAt->copy()->addDays(self::GRACE_PERIOD_DAYS);

            return Carbon::now()->greaterThan($graceEnds);
        }

        // Trial tenants have no subscription_expires_at. Their free-access
        // window is recorded in trial_ends_at (added by the additive migration).
        if (! $expiresAt && $status === 'TRIAL' && $tenant->trial_ends_at) {
            $trialEnds = $tenant->trial_ends_at instanceof Carbon
                ? $tenant->trial_ends_at
                : Carbon::parse($tenant->trial_ends_at);

            return Carbon::now()->greaterThan($trialEnds);
        }

        return false;
    }
}
