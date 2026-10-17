<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\GuestRsvp;
use App\Models\Hall;
use App\Models\HallView;
use App\Models\HallWishlist;
use App\Models\KitchenKot;
use App\Models\Payment;
use App\Models\UtilityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    // Filter controls for Hall Owner
    public string $timeframe = 'MONTHLY'; // 'WEEKLY', 'MONTHLY', 'QUARTERLY', 'YEARLY'
    public string $selectedHallId = 'ALL';
    public string $chartMetric = 'sales'; // 'sales' or 'programs'

    public function setTimeframe(string $tf): void
    {
        if (in_array($tf, ['WEEKLY', 'MONTHLY', 'QUARTERLY', 'YEARLY'])) {
            $this->timeframe = $tf;
        }
    }

    public function setChartMetric(string $metric): void
    {
        if (in_array($metric, ['sales', 'programs'])) {
            $this->chartMetric = $metric;
        }
    }

    public function render()
    {
        $now = Carbon::now();
        $user = Auth::user();
        $tenantId = $user ? $user->tenant_id : null;

        // Fetch Halls strictly scoped by tenant
        $hallsQuery = Hall::where('is_active', true);
        if ($tenantId) {
            $hallsQuery->where('tenant_id', $tenantId);
        } else {
            $hallsQuery->whereRaw('1 = 0');
        }
        $halls = $hallsQuery->get();
        $tenantHallIds = $halls->pluck('id')->toArray();

        // 1. Determine Date Range based on Timeframe
        switch ($this->timeframe) {
            case 'WEEKLY':
                $startDate = $now->copy()->startOfWeek()->format('Y-m-d');
                $endDate = $now->copy()->endOfWeek()->format('Y-m-d');
                $previousStartDate = $now->copy()->subWeek()->startOfWeek()->format('Y-m-d');
                $previousEndDate = $now->copy()->subWeek()->endOfWeek()->format('Y-m-d');
                $periodLabel = 'This Week (' . Carbon::parse($startDate)->format('d M') . ' - ' . Carbon::parse($endDate)->format('d M') . ')';
                break;

            case 'QUARTERLY':
                $startDate = $now->copy()->subDays(90)->format('Y-m-d');
                $endDate = $now->copy()->format('Y-m-d');
                $previousStartDate = $now->copy()->subDays(180)->format('Y-m-d');
                $previousEndDate = $now->copy()->subDays(90)->format('Y-m-d');
                $periodLabel = 'Last 90 Days';
                break;

            case 'YEARLY':
                $startDate = $now->copy()->startOfYear()->format('Y-m-d');
                $endDate = $now->copy()->endOfYear()->format('Y-m-d');
                $previousStartDate = $now->copy()->subYear()->startOfYear()->format('Y-m-d');
                $previousEndDate = $now->copy()->subYear()->endOfYear()->format('Y-m-d');
                $periodLabel = 'Year ' . $now->year;
                break;

            case 'MONTHLY':
            default:
                $startDate = $now->copy()->startOfMonth()->format('Y-m-d');
                $endDate = $now->copy()->endOfMonth()->format('Y-m-d');
                $previousStartDate = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                $previousEndDate = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                $periodLabel = 'This Month (' . $now->format('F Y') . ')';
                break;
        }

        // 2. Base Query strictly scoped to Timeframe and Tenant
        $bookingsQuery = Booking::whereBetween('event_date', [$startDate, $endDate])
            ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);

        $prevQuery = Booking::whereBetween('event_date', [$previousStartDate, $previousEndDate])
            ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);

        if ($tenantId) {
            $bookingsQuery->where('tenant_id', $tenantId);
            $prevQuery->where('tenant_id', $tenantId);
        } else {
            $bookingsQuery->whereRaw('1 = 0');
            $prevQuery->whereRaw('1 = 0');
        }

        if ($this->selectedHallId !== 'ALL' && in_array($this->selectedHallId, $tenantHallIds)) {
            $bookingsQuery->where('hall_id', $this->selectedHallId);
            $prevQuery->where('hall_id', $this->selectedHallId);
        }

        $allPeriodBookings = (clone $bookingsQuery)->with(['hall', 'package', 'payments', 'kitchenKot', 'utilityLog'])->get();

        $prevProgramsCount = (clone $prevQuery)->count();
        $prevSalesTurnover = (float) (clone $prevQuery)->sum('grand_total');

        // 3. Core Financial & Program Metrics
        $totalPrograms = $allPeriodBookings->count();
        $confirmedPrograms = $allPeriodBookings->where('status', 'CONFIRMED')->count();
        $holdPrograms = $allPeriodBookings->where('status', 'HOLD')->count();

        $grossSalesTurnover = (float) $allPeriodBookings->sum('grand_total');
        $advanceCollected = (float) $allPeriodBookings->sum('advance_paid');
        $totalBalanceDue = (float) $allPeriodBookings->sum('balance_due');
        $totalGuestsServed = (int) $allPeriodBookings->sum('guest_count');

        // Growth percentages (Safely calculated: zero when both are zero, never misleading 100%)
        if ($prevProgramsCount > 0) {
            $programsGrowth = round((($totalPrograms - $prevProgramsCount) / $prevProgramsCount) * 100, 1);
        } else {
            $programsGrowth = $totalPrograms > 0 ? 100.0 : 0.0;
        }

        if ($prevSalesTurnover > 0) {
            $salesGrowth = round((($grossSalesTurnover - $prevSalesTurnover) / $prevSalesTurnover) * 100, 1);
        } else {
            $salesGrowth = $grossSalesTurnover > 0 ? 100.0 : 0.0;
        }

        // 4. Actual Recorded Overheads vs Benchmark Cost Estimation
        // Actual Recorded Expenses from ERP Expense Register
        $expenseQuery = Expense::whereBetween('expense_date', [$startDate, $endDate]);
        if ($tenantId) {
            $expenseQuery->where('tenant_id', $tenantId);
        } else {
            $expenseQuery->whereRaw('1 = 0');
        }
        if ($this->selectedHallId !== 'ALL' && in_array($this->selectedHallId, $tenantHallIds)) {
            $expenseQuery->where(function ($q) {
                $q->where('hall_id', $this->selectedHallId)->orWhereNull('hall_id');
            });
        }
        $actualRecordedExpenses = (float) $expenseQuery->sum('amount');

        // Industry Benchmark Catering Provisions (approx. 52% of per-head food component)
        $foodTurnover = (float) $allPeriodBookings->sum(function ($b) {
            return (float) $b->per_head_rate * (int) $b->guest_count;
        });
        $estimatedFoodCost = round($foodTurnover * 0.52, 2);

        // Utility diesel & Wapda cost from utility logs for period bookings
        $periodBookingIds = $allPeriodBookings->pluck('id');
        $dieselLiters = (float) UtilityLog::whereIn('booking_id', $periodBookingIds)->sum('diesel_liters_consumed');
        $totalUtilityCost = (float) UtilityLog::whereIn('booking_id', $periodBookingIds)->sum('total_utility_cost');

        // Net Operating Profit: uses actual recorded expenses if available, otherwise baseline overhead
        $totalOperationalCost = $actualRecordedExpenses > 0 ? $actualRecordedExpenses : ($estimatedFoodCost + $totalUtilityCost);
        $netOwnerProfit = $grossSalesTurnover - $totalOperationalCost;
        $profitMarginPercent = $grossSalesTurnover > 0 
            ? round(($netOwnerProfit / $grossSalesTurnover) * 100, 1) 
            : 0;

        // 5. Programs Breakdown by Event Type
        $eventTypesBreakdown = $allPeriodBookings->groupBy('event_type')->map(function ($items, $type) {
            return [
                'type' => $type,
                'count' => $items->count(),
                'sales' => (float) $items->sum('grand_total'),
                'guests' => (int) $items->sum('guest_count'),
            ];
        })->sortByDesc('sales');

        // 6. Shift Occupancy Rate (Afternoon, Evening, Night)
        $shiftCounts = [
            'AFTERNOON' => $allPeriodBookings->where('slot_type', 'AFTERNOON')->count(),
            'EVENING' => $allPeriodBookings->where('slot_type', 'EVENING')->count(),
            'NIGHT' => $allPeriodBookings->where('slot_type', 'NIGHT')->count(),
        ];
        $totalShifts = max(1, array_sum($shiftCounts));
        $shiftPercentages = [
            'AFTERNOON' => round(($shiftCounts['AFTERNOON'] / $totalShifts) * 100),
            'EVENING' => round(($shiftCounts['EVENING'] / $totalShifts) * 100),
            'NIGHT' => round(($shiftCounts['NIGHT'] / $totalShifts) * 100),
        ];

        // 7. Hall-by-Hall Performance Matrix (Scoped to Owner's Halls)
        $hallPerformance = $halls->map(function ($h) use ($allPeriodBookings) {
            $hallBookings = $allPeriodBookings->where('hall_id', $h->id);
            return [
                'id' => $h->id,
                'name' => $h->name,
                'programs' => $hallBookings->count(),
                'sales' => (float) $hallBookings->sum('grand_total'),
                'advance' => (float) $hallBookings->sum('advance_paid'),
                'guests' => (int) $hallBookings->sum('guest_count'),
                'avgPerProgram' => $hallBookings->count() > 0 ? round($hallBookings->sum('grand_total') / $hallBookings->count()) : 0,
            ];
        });

        // 8. Trend Series for Visual SVG Bar Chart
        $chartData = $this->buildChartData($this->timeframe, $this->selectedHallId, $this->chartMetric, $tenantHallIds, $tenantId);

        // 9. Today's active functions (Strictly Scoped to Owner's Tenant)
        $today = $now->format('Y-m-d');
        $todayQuery = Booking::with(['hall.venue', 'payments', 'kitchenKot', 'utilityLog'])
            ->whereDate('event_date', $today)
            ->whereIn('status', ['CONFIRMED', 'HOLD']);

        if ($tenantId) {
            $todayQuery->where('tenant_id', $tenantId);
        } else {
            $todayQuery->whereRaw('1 = 0');
        }

        if ($this->selectedHallId !== 'ALL' && in_array($this->selectedHallId, $tenantHallIds)) {
            $todayQuery->where('hall_id', $this->selectedHallId);
        }
        $todayBookings = $todayQuery->orderBy('slot_type')->get();

        // 10. Horizon bookings list (Next 6 functions)
        $upcomingQuery = Booking::with(['hall', 'package'])
            ->where('event_date', '>=', $today)
            ->whereIn('status', ['CONFIRMED', 'HOLD'])
            ->orderBy('event_date')
            ->orderBy('slot_type')
            ->limit(6);

        if ($tenantId) {
            $upcomingQuery->where('tenant_id', $tenantId);
        } else {
            $upcomingQuery->whereRaw('1 = 0');
        }

        if ($this->selectedHallId !== 'ALL' && in_array($this->selectedHallId, $tenantHallIds)) {
            $upcomingQuery->where('hall_id', $this->selectedHallId);
        }
        $upcomingBookingsList = $upcomingQuery->get();

        // 11. Quick counts for top operational snapshot
        $totalHallsCount = $halls->count();
        $todayEventsCount = $todayBookings->count();
        $upcomingEventsCount = Booking::where('event_date', '>=', $today)
            ->whereIn('status', ['CONFIRMED', 'HOLD'])
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->count();
        $pendingInquiriesCount = Booking::whereIn('status', ['INQUIRY', 'PENDING'])
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->count();

        // 12. Hall Analytics: Views, Wishlists, Bookings per hall
        $hallAnalytics = $halls->map(function ($h) use ($tenantId) {
            $views = HallView::where('hall_id', $h->id)->count();
            $wishlists = HallWishlist::where('hall_id', $h->id)->count();
            $bookings = Booking::where('hall_id', $h->id)
                ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                ->count();
            $confirmedBookings = Booking::where('hall_id', $h->id)
                ->where('status', 'CONFIRMED')
                ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                ->count();
            $totalRevenue = (float) Booking::where('hall_id', $h->id)
                ->whereIn('status', ['CONFIRMED', 'COMPLETED'])
                ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                ->sum('grand_total');

            return [
                'id' => $h->id,
                'name' => $h->name,
                'slug' => $h->slug,
                'views' => $views,
                'wishlists' => $wishlists,
                'total_bookings' => $bookings,
                'confirmed_bookings' => $confirmedBookings,
                'total_revenue' => $totalRevenue,
            ];
        })->sortByDesc('views');

        $totalHallViews = HallView::where('tenant_id', $tenantId)->count();
        $totalHallWishlists = HallWishlist::where('tenant_id', $tenantId)->count();
        $totalHallBookings = Booking::where('tenant_id', $tenantId)->count();

        return view('livewire.dashboard', [
            'halls' => $halls,
            'periodLabel' => $periodLabel,
            'allPeriodBookings' => $allPeriodBookings,
            'totalPrograms' => $totalPrograms,
            'confirmedPrograms' => $confirmedPrograms,
            'holdPrograms' => $holdPrograms,
            'grossSalesTurnover' => $grossSalesTurnover,
            'advanceCollected' => $advanceCollected,
            'totalBalanceDue' => $totalBalanceDue,
            'totalGuestsServed' => $totalGuestsServed,
            'programsGrowth' => $programsGrowth,
            'salesGrowth' => $salesGrowth,
            'actualRecordedExpenses' => $actualRecordedExpenses,
            'estimatedFoodCost' => $estimatedFoodCost,
            'totalUtilityCost' => $totalUtilityCost,
            'netOwnerProfit' => $netOwnerProfit,
            'profitMarginPercent' => $profitMarginPercent,
            'eventTypesBreakdown' => $eventTypesBreakdown,
            'shiftCounts' => $shiftCounts,
            'shiftPercentages' => $shiftPercentages,
            'hallPerformance' => $hallPerformance,
            'chartData' => $chartData,
            'todayBookings' => $todayBookings,
            'upcomingBookingsList' => $upcomingBookingsList,
            'totalHallsCount' => $totalHallsCount,
            'todayEventsCount' => $todayEventsCount,
            'upcomingEventsCount' => $upcomingEventsCount,
            'pendingInquiriesCount' => $pendingInquiriesCount,
            'hallAnalytics' => $hallAnalytics,
            'totalHallViews' => $totalHallViews,
            'totalHallWishlists' => $totalHallWishlists,
            'totalHallBookings' => $totalHallBookings,
        ]);
    }

    private function buildChartData(string $timeframe, string $hallId, string $metric, array $allowedHallIds = [], ?string $tenantId = null): array
    {
        $now = Carbon::now();
        $points = [];

        if ($timeframe === 'WEEKLY') {
            // 7 Days of the week (Monday to Sunday)
            $start = $now->copy()->startOfWeek();
            for ($i = 0; $i < 7; $i++) {
                $day = $start->copy()->addDays($i);
                $q = Booking::whereDate('event_date', $day->format('Y-m-d'))
                    ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);

                if ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                } else {
                    $q->whereRaw('1 = 0');
                }
                
                if ($hallId !== 'ALL' && in_array($hallId, $allowedHallIds)) {
                    $q->where('hall_id', $hallId);
                } elseif (!empty($allowedHallIds)) {
                    $q->whereIn('hall_id', $allowedHallIds);
                }

                $val = ($metric === 'sales') ? (float) $q->sum('grand_total') : (int) $q->count();
                $points[] = [
                    'label' => $day->format('D (d)'),
                    'value' => $val,
                    'formatted' => ($metric === 'sales') ? 'PKR ' . number_format($val) : $val . ' Events',
                ];
            }
        } elseif ($timeframe === 'YEARLY') {
            // 12 Months
            for ($m = 1; $m <= 12; $m++) {
                $mDate = Carbon::create($now->year, $m, 1);
                $q = Booking::whereBetween('event_date', [$mDate->copy()->startOfMonth()->format('Y-m-d'), $mDate->copy()->endOfMonth()->format('Y-m-d')])
                    ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);

                if ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                } else {
                    $q->whereRaw('1 = 0');
                }
                
                if ($hallId !== 'ALL' && in_array($hallId, $allowedHallIds)) {
                    $q->where('hall_id', $hallId);
                } elseif (!empty($allowedHallIds)) {
                    $q->whereIn('hall_id', $allowedHallIds);
                }

                $val = ($metric === 'sales') ? (float) $q->sum('grand_total') : (int) $q->count();
                $points[] = [
                    'label' => $mDate->format('M'),
                    'value' => $val,
                    'formatted' => ($metric === 'sales') ? 'PKR ' . number_format($val) : $val . ' Events',
                ];
            }
        } else {
            // MONTHLY: 4 Weeks breakdown
            $start = $now->copy()->startOfMonth();
            for ($w = 1; $w <= 4; $w++) {
                $wStart = $start->copy()->addDays(($w - 1) * 7);
                $wEnd = ($w === 4) ? $now->copy()->endOfMonth() : $wStart->copy()->addDays(6);

                $q = Booking::whereBetween('event_date', [$wStart->format('Y-m-d'), $wEnd->format('Y-m-d')])
                    ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);

                if ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                } else {
                    $q->whereRaw('1 = 0');
                }
                
                if ($hallId !== 'ALL' && in_array($hallId, $allowedHallIds)) {
                    $q->where('hall_id', $hallId);
                } elseif (!empty($allowedHallIds)) {
                    $q->whereIn('hall_id', $allowedHallIds);
                }

                $val = ($metric === 'sales') ? (float) $q->sum('grand_total') : (int) $q->count();
                $points[] = [
                    'label' => 'Wk ' . $w . ' (' . $wStart->format('d M') . ')',
                    'value' => $val,
                    'formatted' => ($metric === 'sales') ? 'PKR ' . number_format($val) : $val . ' Events',
                ];
            }
        }

        // Calculate max for SVG bar height scaling
        $maxValue = max(1, ...array_column($points, 'value'));
        foreach ($points as &$p) {
            $p['percentage'] = round(($p['value'] / $maxValue) * 100);
        }

        return $points;
    }
}
