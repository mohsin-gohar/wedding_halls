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
     * Apply subscription lifecycle transitions once per tenant, even when
     * scheduler workers overlap. State changes and audit entries commit
     * atomically while the tenant row is locked.
     */
    public function checkAndExpireSubscriptions(): array
    {
        $now = Carbon::now();
        $expiredCount = 0;
        $warningCount = 0;
        $processedTenants = [];

        Tenant::query()
            ->whereIn('subscription_status', ['ACTIVE', 'TRIAL', 'GRACE_PERIOD'])
            ->where(function ($query): void {
                $query->whereNotNull('subscription_expires_at')
                    ->orWhere(function ($trialQuery): void {
                        $trialQuery->where('subscription_status', 'TRIAL')
                            ->whereNotNull('trial_ends_at');
                    });
            })
            ->select('id')
            ->chunkById(100, function ($candidates) use ($now, &$expiredCount, &$warningCount, &$processedTenants): void {
                foreach ($candidates as $candidate) {
                    $transition = DB::transaction(function () use ($candidate, $now): ?array {
                        $tenant = Tenant::query()
                            ->whereKey($candidate->getKey())
                            ->lockForUpdate()
                            ->first();

                        if (! $tenant || ! in_array($tenant->subscription_status, ['ACTIVE', 'TRIAL', 'GRACE_PERIOD'], true)) {
                            return null;
                        }

                        // Trial-only tenants may have no paid subscription expiry.
                        // Their explicit trial_ends_at date is the entitlement boundary.
                        if (! $tenant->subscription_expires_at) {
                            if ($tenant->subscription_status !== 'TRIAL' || ! $tenant->trial_ends_at || ! $now->greaterThan($tenant->trial_ends_at)) {
                                return null;
                            }

                            $tenant->update(['subscription_status' => 'EXPIRED']);
                            AuditLog::log(
                                'SUBSCRIPTION_EXPIRED',
                                "Tenant [{$tenant->name}] trial ended on [{$tenant->trial_ends_at->toDateTimeString()}] and transitioned to [EXPIRED].",
                                $tenant->id,
                                'Tenant',
                                (string) $tenant->id
                            );

                            return [
                                'tenant_id' => $tenant->id,
                                'name' => $tenant->name,
                                'action' => 'EXPIRED',
                                'expired_at' => $tenant->trial_ends_at->toIso8601String(),
                            ];
                        }

                        $expiresAt = $tenant->subscription_expires_at;
                        $graceEndsAt = $expiresAt->copy()->addDays(self::GRACE_PERIOD_DAYS);
                        $previousStatus = $tenant->subscription_status;

                        if ($now->greaterThan($graceEndsAt)) {
                            $tenant->update(['subscription_status' => 'EXPIRED']);
                            AuditLog::log(
                                'SUBSCRIPTION_EXPIRED',
                                "Tenant [{$tenant->name}] transitioned from [{$previousStatus}] to [EXPIRED]. Access restricted to renewal portal. All historical hall and booking data preserved.",
                                $tenant->id,
                                'Tenant',
                                (string) $tenant->id
                            );

                            return [
                                'tenant_id' => $tenant->id,
                                'name' => $tenant->name,
                                'action' => 'EXPIRED',
                                'expired_at' => $expiresAt->toIso8601String(),
                            ];
                        }

                        if ($now->greaterThan($expiresAt) && $previousStatus !== 'GRACE_PERIOD') {
                            $tenant->update(['subscription_status' => 'GRACE_PERIOD']);
                            AuditLog::log(
                                'SUBSCRIPTION_GRACE_PERIOD',
                                "Tenant [{$tenant->name}] entered 3-day grace period. Final expiry will occur on [{$graceEndsAt->format('Y-m-d')}].",
                                $tenant->id,
                                'Tenant',
                                (string) $tenant->id
                            );

                            return [
                                'tenant_id' => $tenant->id,
                                'name' => $tenant->name,
                                'action' => 'GRACE_PERIOD',
                                'grace_ends_at' => $graceEndsAt->toIso8601String(),
                            ];
                        }

                        return null;
                    });

                    if ($transition === null) {
                        continue;
                    }

                    if ($transition['action'] === 'EXPIRED') {
                        $expiredCount++;
                    } else {
                        $warningCount++;
                    }

                    $processedTenants[] = $transition;
                }
            }, 'id');

        return [
            'expired_count' => $expiredCount,
            'warning_count' => $warningCount,
            'details' => $processedTenants,
        ];
    }

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

        if ($autoApprove) {
            if (! $approvedByUserId) {
                throw new \InvalidArgumentException('Instant subscription approval requires an approving Super Admin.');
            }

            $approver = User::query()->findOrFail($approvedByUserId);
            if (! $approver->isSuperAdmin()) {
                throw new \InvalidArgumentException('Only a Super Admin may instantly approve a subscription.');
            }
        }

        return DB::transaction(function () use ($tenant, $plan, $billingCycle, $amount, $paymentMethod, $transactionReference, $autoApprove, $approvedByUserId): SubscriptionPayment {
            // Serialize renewals for the same tenant. A stale caller model must
            // never overwrite a more recent expiry when two approvals overlap.
            $lockedTenant = Tenant::query()
                ->whereKey($tenant->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $payment = SubscriptionPayment::create([
                'tenant_id' => $lockedTenant->id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'transaction_reference' => $transactionReference ?? ('RG-PAY-'.strtoupper(bin2hex(random_bytes(4)))),
                'status' => $autoApprove ? 'APPROVED' : 'PENDING',
                'approved_by_user_id' => $autoApprove ? $approvedByUserId : null,
                'approved_at' => $autoApprove ? Carbon::now() : null,
                'notes' => $autoApprove ? 'Instant renewal activated by Super Admin.' : 'Manual cash payment submitted; pending Super Admin verification.',
            ]);

            if ($autoApprove) {
                $this->applyApprovedSubscription($lockedTenant, $plan, $billingCycle);
            }

            return $payment;
        });
    }

    public function applyApprovedSubscription(Tenant $tenant, SubscriptionPlan $plan, string $billingCycle): void
    {
        $now = Carbon::now();
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

    public function isLocked(Tenant $tenant): bool
    {
        $status = strtoupper((string) $tenant->subscription_status);

        if (in_array($status, ['SUSPENDED', 'EXPIRED', 'CANCELLED'], true)) {
            return true;
        }

        $expiresAt = $tenant->subscription_expires_at;
        if ($expiresAt && $expiresAt->isPast()) {
            return Carbon::now()->greaterThan($expiresAt->copy()->addDays(self::GRACE_PERIOD_DAYS));
        }

        if (! $expiresAt && $status === 'TRIAL' && $tenant->trial_ends_at) {
            $trialEnds = $tenant->trial_ends_at instanceof Carbon
                ? $tenant->trial_ends_at
                : Carbon::parse($tenant->trial_ends_at);

            return Carbon::now()->greaterThan($trialEnds);
        }

        return false;
    }
}
