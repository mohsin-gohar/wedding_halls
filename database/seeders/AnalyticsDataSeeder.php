<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\KitchenKot;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AnalyticsDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) return;

        $frontDeskUser = User::where('role', 'ROLE_FRONT_DESK')->first() ?? User::first();
        $halls = Hall::where('tenant_id', $tenant->id)->get();
        $packages = Package::where('tenant_id', $tenant->id)->get();

        if ($halls->isEmpty() || $packages->isEmpty()) return;

        $hallRoyal = $halls->firstWhere('name', 'The Royal Ballroom') ?? $halls[0];
        $hallLawn = $halls->firstWhere('name', 'Executive Crystal Lawn') ?? ($halls[1] ?? $halls[0]);
        $hallImperial = $halls->firstWhere('name', 'Imperial Banquet Suite') ?? ($halls[2] ?? $halls[0]);

        $pkgBarat = $packages->firstWhere('name', 'Royal Barat Feast') ?? $packages[0];
        $pkgValima = $packages->firstWhere('name', 'Platinum Valima Buffet') ?? ($packages[1] ?? $packages[0]);
        $pkgMehndi = $packages->firstWhere('name', 'Mehndi Festive Night Feast') ?? ($packages[2] ?? $packages[0]);

        $now = Carbon::now();

        // Sample customer pool
        $sampleClients = [
            ['Malik Naveed Awan', '+92 300 5511223', '35201-9988771-1'],
            ['Sardar Usman Dogar', '+92 321 4433221', '35202-1122334-5'],
            ['Mian Hamza Shahbaz', '+92 333 8877665', '35201-7788990-3'],
            ['Syed Ali Raza Gardezi', '+92 301 9988112', '35202-4455667-9'],
            ['Chaudhry Faisal Warraich', '+92 345 6677889', '35201-3322114-7'],
            ['Dr. Zeeshan Haider', '+92 322 7766554', '35202-8877665-1'],
            ['Khawaja Farooq Ahmed', '+92 300 1144778', '35201-6655443-3'],
            ['Sheikh Daniyal Munir', '+92 334 2233445', '35202-5544332-9'],
            ['Barrister Omer Qureshi', '+92 302 9911883', '35201-4433221-5'],
            ['Haji Muhammad Rafiq', '+92 313 4455778', '35202-2233445-7'],
        ];

        // Seed 14 realistic bookings across current month, past 2 weeks, and upcoming days
        $eventsPlan = [
            // Past week
            [-12, $hallRoyal, $pkgBarat, 'Barat', 'EVENING', 600, 2450.00, 150000.00, 'CONFIRMED'],
            [-10, $hallLawn, $pkgValima, 'Valima', 'EVENING', 850, 2750.00, 220000.00, 'CONFIRMED'],
            [-8, $hallImperial, $pkgMehndi, 'Mehndi', 'NIGHT', 320, 1950.00, 110000.00, 'CONFIRMED'],
            [-6, $hallRoyal, $pkgBarat, 'Barat', 'EVENING', 550, 2450.00, 150000.00, 'CONFIRMED'],
            [-4, $hallLawn, $pkgValima, 'Valima', 'EVENING', 900, 2750.00, 220000.00, 'CONFIRMED'],
            [-2, $hallImperial, $pkgMehndi, 'Qawwali Night', 'NIGHT', 280, 2100.00, 110000.00, 'CONFIRMED'],
            // This week
            [-1, $hallRoyal, $pkgBarat, 'Barat', 'AFTERNOON', 450, 2450.00, 150000.00, 'CONFIRMED'],
            [0, $hallRoyal, $pkgBarat, 'Barat', 'EVENING', 700, 2450.00, 150000.00, 'CONFIRMED'],
            [1, $hallLawn, $pkgValima, 'Valima', 'EVENING', 1000, 2750.00, 220000.00, 'CONFIRMED'],
            [2, $hallImperial, $pkgMehndi, 'Mehndi', 'EVENING', 350, 1950.00, 110000.00, 'CONFIRMED'],
            [3, $hallRoyal, $pkgBarat, 'Barat', 'EVENING', 650, 2450.00, 150000.00, 'CONFIRMED'],
            [5, $hallLawn, $pkgValima, 'Valima', 'EVENING', 800, 2750.00, 220000.00, 'CONFIRMED'],
            [7, $hallImperial, $pkgMehndi, 'Engagement', 'AFTERNOON', 200, 1950.00, 110000.00, 'HOLD'],
            [10, $hallRoyal, $pkgBarat, 'Barat', 'EVENING', 500, 2450.00, 150000.00, 'HOLD'],
        ];

        foreach ($eventsPlan as $idx => $event) {
            [$offsetDays, $hall, $pkg, $type, $slot, $guests, $perHead, $rent, $status] = $event;
            $client = $sampleClients[$idx % count($sampleClients)];
            $eventDate = $now->copy()->addDays($offsetDays)->format('Y-m-d');

            // Skip if booking already exists for this slot
            $exists = Booking::where('hall_id', $hall->id)
                ->whereDate('event_date', $eventDate)
                ->where('slot_type', $slot)
                ->exists();

            if ($exists) continue;

            $addonsTotal = rand(2, 5) * 10000.00;
            $discount = ($idx % 3 === 0) ? 25000.00 : 0.00;
            $subtotal = ($perHead * $guests) + $rent + $addonsTotal - $discount;
            $tax = round($subtotal * 0.05, 2);
            $grandTotal = $subtotal + $tax;
            $advancePaid = ($status === 'CONFIRMED') ? round($grandTotal * 0.55, 2) : 50000.00;
            $balanceDue = $grandTotal - $advancePaid;

            try {
                $booking = Booking::create([
                    'tenant_id' => $tenant->id,
                    'hall_id' => $hall->id,
                    'package_id' => $pkg->id,
                    'created_by_user_id' => $frontDeskUser->id,
                    'customer_name' => $client[0],
                    'customer_phone' => $client[1],
                    'customer_cnic' => $client[2],
                    'customer_email' => strtolower(str_replace(' ', '.', $client[0])) . '@example.com',
                    'event_type' => $type,
                    'event_date' => $eventDate,
                    'slot_type' => $slot,
                    'guest_count' => $guests,
                    'per_head_rate' => $perHead,
                    'hall_rent' => $rent,
                    'addons_total' => $addonsTotal,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'grand_total' => $grandTotal,
                    'advance_paid' => $advancePaid,
                    'balance_due' => $balanceDue,
                    'status' => $status,
                    'notes' => 'Stage decor gold floral, heavy air conditioning, VIP family lounge.',
                ]);

                // Add Payment
                Payment::create([
                    'tenant_id' => $tenant->id,
                    'booking_id' => $booking->id,
                    'received_by_user_id' => $frontDeskUser->id,
                    'receipt_no' => 'RCT-' . date('Ym') . '-' . rand(1000, 9999),
                    'amount' => $advancePaid,
                    'payment_method' => ($idx % 2 === 0) ? 'CASH' : 'BANK_TRANSFER',
                    'payment_type' => 'TOKEN_ADVANCE',
                    'transaction_reference' => 'TXN-MB-' . rand(100000, 999999),
                    'notes' => 'Token advance collected at front desk counter.',
                ]);

                // Add Kitchen KOT
                KitchenKot::create([
                    'tenant_id' => $tenant->id,
                    'booking_id' => $booking->id,
                    'kot_number' => 'KOT-' . date('Ym') . '-' . rand(1000, 9999),
                    'guest_count' => $guests,
                    'raw_meat_kg' => round($guests * 0.12, 1),
                    'raw_rice_kg' => round($guests * 0.16, 1),
                    'raw_oil_liters' => round($guests * 0.025, 1),
                    'preparation_status' => ($offsetDays < 0) ? 'SERVED' : (($offsetDays === 0) ? 'PREPARING' : 'PENDING'),
                    'dishes_summary' => [
                        'Mains' => [$pkg->name],
                        'Rice' => ['Special Basmati Biryani / Pulao'],
                        'Bread' => ['Fresh Roghni Naan'],
                        'Dessert' => ['Hot Gulab Jamun', 'Zafrani Kheer'],
                    ],
                ]);

                // Add Utility Log for past/current events
                if ($offsetDays <= 0) {
                    $runHours = rand(3, 5);
                    $dieselLiters = $runHours * 18;
                    $dieselCost = $dieselLiters * 285;
                    $wapdaUnits = rand(200, 350);
                    $wapdaCost = $wapdaUnits * 62;

                    UtilityLog::create([
                        'tenant_id' => $tenant->id,
                        'booking_id' => $booking->id,
                        'generator_run_hours' => $runHours,
                        'diesel_liters_consumed' => $dieselLiters,
                        'diesel_rate_per_liter' => 285.00,
                        'wapda_units_consumed' => $wapdaUnits,
                        'wapda_unit_rate' => 62.00,
                        'total_utility_cost' => $dieselCost + $wapdaCost,
                        'logged_by' => $frontDeskUser->id,
                        'notes' => 'Outage between 8:00 PM and 11:30 PM. Generator ran flawlessly.',
                    ]);
                }
            } catch (\Exception $e) {
                // slot occupied or error, skip
                continue;
            }
        }
    }
}
