<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\SubscriptionPayment;
use App\Support\PlatformMetrics;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the Super Admin dashboard charts.
 *
 * Extracted from the Livewire component so the console stays maintainable and
 * the chart aggregations can be cached independently of the page render.
 */
class PlatformAnalyticsService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function chartData(string $timeframe, string $metric): array
    {
        $cacheKey = 'platform:analytics:chart:'.PlatformMetrics::version().':'.$timeframe.':'.$metric.':'.now()->format('Ymd');

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($timeframe, $metric): array {
            $now = Carbon::now();
            $points = [];

            if ($timeframe === 'WEEKLY') {
                $start = $now->copy()->startOfWeek();
                for ($i = 0; $i < 7; $i++) {
                    $day = $start->copy()->addDays($i);
                    $baseQ = Booking::whereDate('event_date', $day->format('Y-m-d'))
                        ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);

                    $eveningQ = (clone $baseQ)->where('slot_type', 'EVENING');
                    $afternoonQ = (clone $baseQ)->where('slot_type', 'AFTERNOON');

                    $val = ($metric === 'gmv') ? (float) $eveningQ->sum('grand_total') : (int) $eveningQ->count();
                    $secVal = ($metric === 'gmv') ? (float) $afternoonQ->sum('grand_total') : (int) $afternoonQ->count();
                    $totalVal = ($metric === 'gmv') ? (float) $baseQ->sum('grand_total') : (int) $baseQ->count();

                    $points[] = [
                        'label' => $day->format('D'),
                        'fullLabel' => $day->format('D, d M'),
                        'value' => $val,
                        'secondaryValue' => $secVal,
                        'totalValue' => $totalVal,
                        'formatted' => ($metric === 'gmv') ? 'PKR '.number_format($val) : $val.' Eve',
                        'secondaryFormatted' => ($metric === 'gmv') ? 'PKR '.number_format($secVal) : $secVal.' Aft',
                    ];
                }
            } elseif ($timeframe === 'YEARLY') {
                for ($m = 1; $m <= 12; $m++) {
                    $mDate = Carbon::create($now->year, $m, 1);
                    $baseQ = Booking::whereBetween('event_date', [
                        $mDate->copy()->startOfMonth()->format('Y-m-d'),
                        $mDate->copy()->endOfMonth()->format('Y-m-d'),
                    ])->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);

                    $eveningQ = (clone $baseQ)->where('slot_type', 'EVENING');
                    $afternoonQ = (clone $baseQ)->where('slot_type', 'AFTERNOON');

                    $val = ($metric === 'gmv') ? (float) $eveningQ->sum('grand_total') : (int) $eveningQ->count();
                    $secVal = ($metric === 'gmv') ? (float) $afternoonQ->sum('grand_total') : (int) $afternoonQ->count();
                    $totalVal = ($metric === 'gmv') ? (float) $baseQ->sum('grand_total') : (int) $baseQ->count();

                    $points[] = [
                        'label' => $mDate->format('M'),
                        'fullLabel' => $mDate->format('F Y'),
                        'value' => $val,
                        'secondaryValue' => $secVal,
                        'totalValue' => $totalVal,
                        'formatted' => ($metric === 'gmv') ? 'PKR '.number_format($val) : $val.' Eve',
                        'secondaryFormatted' => ($metric === 'gmv') ? 'PKR '.number_format($secVal) : $secVal.' Aft',
                    ];
                }
            } else {
                $start = $now->copy()->startOfMonth();
                for ($w = 1; $w <= 4; $w++) {
                    $wStart = $start->copy()->addDays(($w - 1) * 7);
                    $wEnd = ($w === 4) ? $now->copy()->endOfMonth() : $wStart->copy()->addDays(6);

                    $baseQ = Booking::whereBetween('event_date', [$wStart->format('Y-m-d'), $wEnd->format('Y-m-d')])
                        ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);

                    $eveningQ = (clone $baseQ)->where('slot_type', 'EVENING');
                    $afternoonQ = (clone $baseQ)->where('slot_type', 'AFTERNOON');

                    $val = ($metric === 'gmv') ? (float) $eveningQ->sum('grand_total') : (int) $eveningQ->count();
                    $secVal = ($metric === 'gmv') ? (float) $afternoonQ->sum('grand_total') : (int) $afternoonQ->count();
                    $totalVal = ($metric === 'gmv') ? (float) $baseQ->sum('grand_total') : (int) $baseQ->count();

                    $points[] = [
                        'label' => 'Wk '.$w,
                        'fullLabel' => 'Week '.$w.' ('.$wStart->format('d M').' - '.$wEnd->format('d M').')',
                        'value' => $val,
                        'secondaryValue' => $secVal,
                        'totalValue' => $totalVal,
                        'formatted' => ($metric === 'gmv') ? 'PKR '.number_format($val) : $val.' Eve',
                        'secondaryFormatted' => ($metric === 'gmv') ? 'PKR '.number_format($secVal) : $secVal.' Aft',
                    ];
                }
            }

            $allValues = [];
            foreach ($points as $p) {
                $allValues[] = $p['value'];
                $allValues[] = $p['secondaryValue'];
            }
            $maxValue = max(1, ...$allValues);

            $peakIndex = 0;
            $highestCombined = -1;
            foreach ($points as $idx => $p) {
                $combined = $p['value'] + $p['secondaryValue'];
                if ($combined > $highestCombined) {
                    $highestCombined = $combined;
                    $peakIndex = $idx;
                }
            }

            foreach ($points as $idx => &$p) {
                $p['percentage'] = round(($p['value'] / $maxValue) * 100);
                $p['secondaryPercentage'] = round(($p['secondaryValue'] / $maxValue) * 100);
                $p['isPeak'] = ($idx === $peakIndex && $highestCombined > 0);
                $p['peakPill'] = ($metric === 'gmv')
                    ? 'PKR '.number_format($p['totalValue'] / 1000).'k'
                    : $p['totalValue'].' Functions';
            }
            unset($p);

            return $points;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function waveData(string $timeframe): array
    {
        $cacheKey = 'platform:analytics:wave:'.PlatformMetrics::version().':'.$timeframe.':'.now()->format('Ymd');

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($timeframe): array {
            $now = Carbon::now();
            $buckets = [];

            if ($timeframe === 'WEEKLY') {
                $start = $now->copy()->startOfWeek();
                for ($i = 0; $i < 7; $i++) {
                    $day = $start->copy()->addDays($i);
                    $dStr = $day->format('Y-m-d');
                    $buckets[] = [
                        'label' => $day->format('D'),
                        'start' => $dStr,
                        'end' => $dStr,
                    ];
                }
            } elseif ($timeframe === 'YEARLY') {
                for ($m = 1; $m <= 12; $m++) {
                    $mDate = Carbon::create($now->year, $m, 1);
                    $buckets[] = [
                        'label' => $mDate->format('M'),
                        'start' => $mDate->copy()->startOfMonth()->format('Y-m-d'),
                        'end' => $mDate->copy()->endOfMonth()->format('Y-m-d'),
                    ];
                }
            } else {
                $start = $now->copy()->startOfMonth();
                for ($w = 1; $w <= 4; $w++) {
                    $wStart = $start->copy()->addDays(($w - 1) * 7);
                    $wEnd = ($w === 4) ? $now->copy()->endOfMonth() : $wStart->copy()->addDays(6);
                    $buckets[] = [
                        'label' => 'Wk '.$w,
                        'start' => $wStart->format('Y-m-d'),
                        'end' => $wEnd->format('Y-m-d'),
                    ];
                }
            }

            $inflowVals = [];
            $subscriptionVals = [];
            $pointsCount = count($buckets);

            foreach ($buckets as $b) {
                $bQ = Booking::whereBetween('event_date', [$b['start'], $b['end']])
                    ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);
                $inflowVals[] = (float) $bQ->sum('grand_total');

                $sQ = SubscriptionPayment::whereBetween('created_at', [$b['start'].' 00:00:00', $b['end'].' 23:59:59'])
                    ->where('status', 'APPROVED');
                $subscriptionVals[] = (float) $sQ->sum('amount');
            }

            $maxVal = max(1, ...$inflowVals, ...$subscriptionVals);
            $chartW = 600;
            $topPad = 20;
            $bottomBase = 120;
            $usableH = $bottomBase - $topPad;
            $stepX = ($pointsCount > 1) ? ($chartW - 60) / ($pointsCount - 1) : 0;

            $inflowCoords = [];
            $subscriptionCoords = [];

            for ($i = 0; $i < $pointsCount; $i++) {
                $x = round(30 + ($i * $stepX), 1);
                $yInflow = round($bottomBase - (($inflowVals[$i] / $maxVal) * $usableH), 1);
                $ySub = round($bottomBase - (($subscriptionVals[$i] / $maxVal) * $usableH), 1);

                $inflowCoords[] = [
                    'x' => $x,
                    'y' => $yInflow,
                    'val' => $inflowVals[$i],
                    'label' => $buckets[$i]['label'],
                    'formatted' => 'PKR '.number_format($inflowVals[$i]),
                ];
                $subscriptionCoords[] = [
                    'x' => $x,
                    'y' => $ySub,
                    'val' => $subscriptionVals[$i],
                    'label' => $buckets[$i]['label'],
                    'formatted' => 'PKR '.number_format($subscriptionVals[$i]),
                ];
            }

            $buildSpline = function (array $coords) use ($bottomBase) {
                if (empty($coords)) {
                    return ['path' => '', 'area' => ''];
                }
                $path = "M {$coords[0]['x']} {$coords[0]['y']}";
                for ($i = 1; $i < count($coords); $i++) {
                    $prev = $coords[$i - 1];
                    $curr = $coords[$i];
                    $cpx1 = round($prev['x'] + ($curr['x'] - $prev['x']) / 2, 1);
                    $cpy1 = $prev['y'];
                    $cpx2 = round($prev['x'] + ($curr['x'] - $prev['x']) / 2, 1);
                    $cpy2 = $curr['y'];
                    $path .= " C {$cpx1} {$cpy1}, {$cpx2} {$cpy2}, {$curr['x']} {$curr['y']}";
                }
                $lastX = end($coords)['x'];
                $firstX = $coords[0]['x'];
                $area = "{$path} L {$lastX} {$bottomBase} L {$firstX} {$bottomBase} Z";

                return ['path' => $path, 'area' => $area];
            };

            $inflowSpline = $buildSpline($inflowCoords);
            $subSpline = $buildSpline($subscriptionCoords);

            return [
                'inflowPath' => $inflowSpline['path'],
                'inflowArea' => $inflowSpline['area'],
                'subscriptionPath' => $subSpline['path'],
                'subscriptionArea' => $subSpline['area'],
                'inflowCoords' => $inflowCoords,
                'subscriptionCoords' => $subscriptionCoords,
                'totalInflow' => array_sum($inflowVals),
                'totalSubscription' => array_sum($subscriptionVals),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function miniCalendar(): array
    {
        $cacheKey = 'platform:analytics:calendar:'.PlatformMetrics::version().':'.now()->format('Ym');

        return Cache::remember($cacheKey, now()->addMinutes(5), function (): array {
            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();
            $daysInMonth = $now->daysInMonth;

            $leadBlanks = ($startOfMonth->dayOfWeekIso - 1);

            $monthBookings = Booking::whereBetween('event_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD'])
                ->get();

            $days = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = $startOfMonth->copy()->addDays($d - 1);
                $dStr = $date->format('Y-m-d');
                $dayBookings = $monthBookings->filter(fn ($b) => Carbon::parse($b->event_date)->format('Y-m-d') === $dStr);

                $days[] = [
                    'day' => $d,
                    'date' => $dStr,
                    'isToday' => ($d === $now->day),
                    'hasEvening' => $dayBookings->contains('slot_type', 'EVENING'),
                    'hasAfternoon' => $dayBookings->contains('slot_type', 'AFTERNOON'),
                    'count' => $dayBookings->count(),
                ];
            }

            $nextBooking = Booking::with('hall')
                ->whereDate('event_date', '>=', $now->format('Y-m-d'))
                ->whereIn('status', ['CONFIRMED', 'HOLD'])
                ->orderBy('event_date')
                ->first();

            return [
                'monthName' => $now->format('F Y'),
                'leadBlanks' => $leadBlanks,
                'days' => $days,
                'nextBooking' => $nextBooking ? [
                    'date' => Carbon::parse($nextBooking->event_date)->format('d M'),
                    'slot' => ucfirst(strtolower($nextBooking->slot_type ?? 'Evening')),
                    'hall' => $nextBooking->hall->name ?? 'Ballroom',
                    'client' => $nextBooking->customer_name ?? 'Client',
                ] : null,
            ];
        });
    }
}
