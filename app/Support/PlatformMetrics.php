<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\HallView;
use App\Models\HallWishlist;
use App\Models\PageVisit;
use App\Models\PlatformTask;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cached, platform-wide counters used by the Super Admin console.
 *
 * The dashboard renders on every Livewire interaction, so recomputing a dozen
 * aggregate queries on each keystroke is wasteful. The snapshot is cached for
 * one minute and explicitly invalidated whenever a platform audit entry is
 * written (which covers every privileged mutation).
 */
final class PlatformMetrics
{
    private const VERSION_KEY = 'platform:metrics:version';

    private const SNAPSHOT_PREFIX = 'platform:metrics:snapshot:';

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(): array
    {
        $key = self::SNAPSHOT_PREFIX.self::version().':'.now()->format('YmdHi');

        return Cache::remember($key, now()->addMinute(), static fn (): array => self::compute());
    }

    /**
     * @return array<string, mixed>
     */
    private static function compute(): array
    {
        $activeSessionsCount = 0;

        try {
            $activeSessionsCount = DB::table('sessions')
                ->where('last_activity', '>=', now()->subMinutes(15)->getTimestamp())
                ->count();
        } catch (\Throwable) {
            // The sessions table is optional in some deployments.
        }

        if ($activeSessionsCount === 0) {
            $activeSessionsCount = PageVisit::where('visited_at', '>=', now()->subMinutes(15))
                ->distinct('ip_address')
                ->count('ip_address');

            if ($activeSessionsCount === 0) {
                $activeSessionsCount = 1;
            }
        }

        $uniqueVisitorsCount = PageVisit::distinct('ip_address')->count('ip_address');

        if ($uniqueVisitorsCount === 0) {
            $uniqueVisitorsCount = max(1, User::count());
        }

        $shiftRows = Booking::query()
            ->selectRaw('slot_type, COUNT(*) as aggregate')
            ->whereIn('slot_type', ['EVENING', 'AFTERNOON', 'NIGHT'])
            ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD'])
            ->groupBy('slot_type')
            ->pluck('aggregate', 'slot_type');

        $adminShiftCounts = [
            'EVENING' => (int) ($shiftRows['EVENING'] ?? 0),
            'AFTERNOON' => (int) ($shiftRows['AFTERNOON'] ?? 0),
            'NIGHT' => (int) ($shiftRows['NIGHT'] ?? 0),
        ];
        $totalAdminShifts = max(1, array_sum($adminShiftCounts));

        return [
            'totalTenants' => Tenant::count(),
            'pendingTenants' => Tenant::where('verification_status', 'PENDING')->count(),
            'totalHalls' => Hall::count(),
            'totalBookings' => Booking::count(),
            'totalGmv' => (float) Booking::sum('grand_total'),
            'totalPageVisits' => PageVisit::count(),
            'totalHallViews' => HallView::count(),
            'totalWishlists' => HallWishlist::count(),
            'totalRegisteredUsers' => User::count(),
            'todayVisits' => PageVisit::whereDate('visited_at', today())->count(),
            'todayHallViews' => HallView::whereDate('viewed_at', today())->count(),
            'activeSessionsCount' => $activeSessionsCount,
            'uniqueVisitorsCount' => $uniqueVisitorsCount,
            'totalCustomers' => User::whereIn('role', ['CUSTOMER', 'ROLE_CUSTOMER'])->count(),
            'totalVendors' => User::whereIn('role', ['VENDOR', 'HALL_OWNER', 'ADMIN', 'ROLE_TENANT_OWNER'])->count(),
            'totalStaff' => User::whereNotIn('role', [
                'CUSTOMER', 'ROLE_CUSTOMER', 'VENDOR', 'HALL_OWNER', 'ADMIN',
                'ROLE_TENANT_OWNER', 'SUPER_ADMIN', 'ROLE_SUPER_ADMIN',
            ])->count(),
            'totalSuspendedUsers' => User::where('status', 'SUSPENDED')->count(),
            'totalPlans' => SubscriptionPlan::count(),
            'totalSubscriptionPayments' => SubscriptionPayment::count(),
            'totalAuditLogs' => AuditLog::count(),
            'totalPlatformTasks' => PlatformTask::count(),
            'pendingTasksCount' => PlatformTask::where('is_completed', false)->count(),
            'completedTasksCount' => PlatformTask::where('is_completed', true)->count(),
            'pendingApprovalsCount' => Tenant::where('verification_status', 'PENDING')->count(),
            'pendingSubscriptionPaymentsCount' => SubscriptionPayment::where('status', 'PENDING')->count(),
            'adminShiftCounts' => $adminShiftCounts,
            'adminShiftPercentages' => [
                'EVENING' => round(($adminShiftCounts['EVENING'] / $totalAdminShifts) * 100),
                'AFTERNOON' => round(($adminShiftCounts['AFTERNOON'] / $totalAdminShifts) * 100),
                'NIGHT' => round(($adminShiftCounts['NIGHT'] / $totalAdminShifts) * 100),
            ],
        ];
    }
}
