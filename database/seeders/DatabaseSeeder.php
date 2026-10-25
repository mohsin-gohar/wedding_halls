<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\GuestRsvp;
use App\Models\Hall;
use App\Models\KitchenKot;
use App\Models\MenuItem;
use App\Models\Package;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityLog;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles
        $roles = [
            'ROLE_SUPER_ADMIN',
            'ROLE_TENANT_OWNER',
            'ROLE_MANAGER',
            'ROLE_ACCOUNTANT',
            'ROLE_FRONT_DESK',
            'ROLE_CHEF',
            'ROLE_FLOOR_STAFF',
            'ROLE_CUSTOMER',
        ];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 1.1 Subscription Plans (Commercial Pakistani SaaS Tiers in PKR)
        $starterPlan = SubscriptionPlan::create([
            'name' => 'Starter Marquee',
            'slug' => 'starter-marquee',
            'description' => 'Ideal for single wedding marquees and banquet halls starting digital operations.',
            'price_monthly' => 15000.00,
            'price_yearly' => 150000.00,
            'max_halls' => 1,
            'max_staff' => 3,
            'max_monthly_bookings' => 20,
            'has_custom_domain' => false,
            'has_kitchen_kot' => true,
            'has_whatsapp_automation' => false,
            'has_accounting_reports' => true,
            'trial_days' => 14,
            'is_active' => true,
        ]);

        $executivePlan = SubscriptionPlan::create([
            'name' => 'Executive Banquet Suite',
            'slug' => 'executive-banquet-suite',
            'description' => 'Designed for busy multi-hall banquet complexes, catering operations, and full ERP management.',
            'price_monthly' => 35000.00,
            'price_yearly' => 350000.00,
            'max_halls' => 3,
            'max_staff' => 10,
            'max_monthly_bookings' => 60,
            'has_custom_domain' => true,
            'has_kitchen_kot' => true,
            'has_whatsapp_automation' => true,
            'has_accounting_reports' => true,
            'trial_days' => 14,
            'is_active' => true,
        ]);

        $enterprisePlan = SubscriptionPlan::create([
            'name' => 'Royal Imperial Enterprise',
            'slug' => 'royal-imperial-enterprise',
            'description' => 'Unlimited multi-branch hotel banquets, convention centres, and wedding empires across Pakistan.',
            'price_monthly' => 65000.00,
            'price_yearly' => 650000.00,
            'max_halls' => -1,
            'max_staff' => -1,
            'max_monthly_bookings' => -1,
            'has_custom_domain' => true,
            'has_kitchen_kot' => true,
            'has_whatsapp_automation' => true,
            'has_accounting_reports' => true,
            'trial_days' => 30,
            'is_active' => true,
        ]);

        // 2. Tenant
        $tenant = Tenant::create([
            'name' => 'VenueCloud Luxury Hospitality Group',
            'slug' => 'venue-cloud',
            'subdomain' => 'venuecloud',
            'email' => 'management@venuecloud.pk',
            'phone' => '+92 42 35789000',
            'address' => 'Plot 42, Main Boulevard, Gulberg III, Lahore, Pakistan',
            'subscription_plan' => 'ENTERPRISE',
            'subscription_plan_id' => $executivePlan->id,
            'subscription_status' => 'ACTIVE',
            'billing_cycle' => 'YEARLY',
            'subscription_starts_at' => Carbon::now()->subMonths(2),
            'subscription_expires_at' => Carbon::now()->addMonths(10),
            'currency' => 'PKR',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        // 3. Staff Users
        $adminUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sheikh Ahsan (Director)',
            'email' => 'admin@eventhub.com',
            'phone' => '+92 42 35789000',
            'role' => 'ROLE_SUPER_ADMIN',
            'password' => Hash::make('admin1235'),
        ]);
        $adminUser->assignRole('ROLE_SUPER_ADMIN');

        $ownerUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Chaudhry Tariq (Hall Owner)',
            'email' => 'owner@venuecloud.pk',
            'phone' => '+92 300 7654321',
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make('password'),
        ]);
        $ownerUser->assignRole('ROLE_TENANT_OWNER');

        $customerUser = User::create([
            'tenant_id' => null,
            'name' => 'Hamza Ali (Client)',
            'email' => 'customer@example.com',
            'phone' => '+92 301 1122334',
            'role' => 'ROLE_CUSTOMER',
            'password' => Hash::make('password'),
        ]);
        $customerUser->assignRole('ROLE_CUSTOMER');

        $frontDeskUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Usman Tariq (Front Desk)',
            'email' => 'reception@venuecloud.pk',
            'phone' => '+92 321 4455667',
            'role' => 'ROLE_FRONT_DESK',
            'password' => Hash::make('password'),
        ]);
        $frontDeskUser->assignRole('ROLE_FRONT_DESK');

        $chefUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Ustad Niaz Bawarchi (Head Chef)',
            'email' => 'chef@venuecloud.pk',
            'phone' => '+92 333 9988776',
            'role' => 'ROLE_CHEF',
            'password' => Hash::make('password'),
        ]);
        $chefUser->assignRole('ROLE_CHEF');

        // 4. Venue
        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'VenueCloud Grand Marquee Complex',
            'slug' => 'venue-cloud-grand-complex',
            'city' => 'Lahore',
            'area' => 'Gulberg III / Cantt',
            'address' => 'Main Boulevard, Gulberg III, Lahore',
            'phone' => '+92 42 35789000',
            'email' => 'info@venuecloud.pk',
            'description' => 'Flagship 5-star wedding banqueting complex with state-of-the-art climate control and dedicated parking.',
            'is_active' => true,
        ]);

        // 5. Halls
        $defaultServices = [
            [
                'title' => 'VIP Bridal Luxury Dressing Suite',
                'price' => 25000,
                'is_complimentary' => true,
                'category' => 'VIP Hospitality',
                'description' => 'Air-conditioned private suite with attached restroom, illuminated Hollywood makeup vanity, and dedicated dressing concierge.',
                'icon' => 'sparkles',
            ],
            [
                'title' => '500 kVA Caterpillar Standby Power Generator',
                'price' => 35000,
                'is_complimentary' => true,
                'category' => 'Infrastructure',
                'description' => 'Uninterrupted power system with automatic synchronization and fuel backup covering 100% chiller AC load.',
                'icon' => 'bolt',
            ],
            [
                'title' => 'Bespoke Fresh Rose Stage Decor & Walkway',
                'price' => 85000,
                'is_complimentary' => false,
                'category' => 'Decor & Ambience',
                'description' => 'Fresh imported gladiolus, red roses, golden filigree stage sofa, ambient pin spot lighting, and floral aisle runners.',
                'icon' => 'star',
            ],
            [
                'title' => 'Executive Chauffeur Valet Parking Service',
                'price' => 20000,
                'is_complimentary' => false,
                'category' => 'Guest Logistics',
                'description' => 'Dedicated 8-member uniformed valet team with electronic key logging and paved parking for 250+ cars.',
                'icon' => 'truck',
            ],
            [
                'title' => '4K Drone Aerial Cinematography & LED P3 Wall',
                'price' => 60000,
                'is_complimentary' => false,
                'category' => 'Multimedia & Video',
                'description' => 'Dual 16x10 ft P3 ultra-HD stage display screens with live dual-camera vision mixer for program broadcast.',
                'icon' => 'camera',
            ],
        ];

        $hallRoyal = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'The Royal Ballroom',
            'tagline' => 'Pakistans Crown Jewel in Royal Banqueting & Celebrations',
            'min_capacity' => 200,
            'max_capacity' => 850,
            'base_rent' => 150000.00,
            'description' => 'Spectacular Austrian crystal chandeliers, imported wool carpeting, high-ceiling acoustics, and royal dining ergonomics.',
            'operating_hours' => 'Afternoon (11:30 AM - 3:30 PM) | Evening (7:00 PM - 12:00 AM)',
            'policies' => "1. 50% Advance booking deposit required to lock event shift atomically.\n2. Music & external DJ permissible up to 10:00 PM per city banqueting guidelines.\n3. Outside catering subject to executive kitchen utility NOC.",
            'cover_image' => 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop',
            'images' => [
                'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?q=80&w=1200&auto=format&fit=crop',
            ],
            'videos' => [
                'https://www.youtube.com/embed/7wtfhZwyrcc',
            ],
            'services' => $defaultServices,
            'website_settings' => [
                'whatsapp_number' => '+92 300 7654321',
                'phone' => '+92 300 7654321',
            ],
            'has_air_conditioning' => true,
            'has_generator_backup' => true,
            'has_bridal_room' => true,
            'is_active' => true,
        ]);

        $hallCrystal = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Executive Crystal Lawn',
            'tagline' => 'Manicured Botanical Open-Air Elegance with Fairy Light Canopies',
            'min_capacity' => 300,
            'max_capacity' => 1400,
            'base_rent' => 220000.00,
            'description' => 'Open-air manicured botanical lawn with fountain installations, fairytale tree illumination, and expansive dance floor.',
            'operating_hours' => 'Evening (6:30 PM - 11:30 PM) | Night (11:30 PM - 3:30 AM)',
            'policies' => "1. Lawn setup setup begins 4 hours prior to function start time.\n2. In event of unexpected rain, banquet marquee switchover is subject to availability.",
            'cover_image' => 'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=1200&auto=format&fit=crop',
            'images' => [
                'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=1200&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200&auto=format&fit=crop',
            ],
            'videos' => [
                'https://www.youtube.com/embed/7wtfhZwyrcc',
            ],
            'services' => $defaultServices,
            'website_settings' => [
                'whatsapp_number' => '+92 301 5556677',
                'phone' => '+92 301 5556677',
            ],
            'has_air_conditioning' => false,
            'has_generator_backup' => true,
            'has_bridal_room' => true,
            'is_active' => true,
        ]);

        $hallImperial = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Imperial Banquet Suite',
            'tagline' => 'Intimate Opulence Tailored for Mehndi Nights, Qawwalis & Engagements',
            'min_capacity' => 100,
            'max_capacity' => 450,
            'base_rent' => 110000.00,
            'description' => 'Intimate banquet space designed specifically for celebratory Mehndi gatherings, soulful Qawwali evenings, and family engagements.',
            'operating_hours' => 'Afternoon (12:00 PM - 4:00 PM) | Night (8:00 PM - 2:00 AM)',
            'policies' => "1. Traditional dholak and acoustic setups permissible inside hall.\n2. Cold-fire sparkle fireworks allowed with safety protocol.",
            'cover_image' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200&auto=format&fit=crop',
            'images' => [
                'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1520854221256-17451cc331bf?q=80&w=1200&auto=format&fit=crop',
            ],
            'videos' => [
                'https://www.youtube.com/embed/7wtfhZwyrcc',
            ],
            'services' => $defaultServices,
            'website_settings' => [
                'whatsapp_number' => '+92 302 7778899',
                'phone' => '+92 302 7778899',
            ],
            'has_air_conditioning' => true,
            'has_generator_backup' => true,
            'has_bridal_room' => true,
            'is_active' => true,
        ]);

        // 6. Menu Items
        $menuItems = [
            ['Mutton Qorma Mughlai', 'MAINS', 650.00, 0.1200, 0.0000, 0.0200],
            ['Chicken Biryani Special', 'RICE', 380.00, 0.1000, 0.1600, 0.0250],
            ['Mutton Dum Pukht Pulao', 'RICE', 600.00, 0.1200, 0.1600, 0.0200],
            ['Chicken Seekh Kabab', 'STARTERS', 320.00, 0.0800, 0.0000, 0.0100],
            ['Fresh Roghni Naan', 'BREADS', 60.00, 0.0000, 0.0000, 0.0050],
            ['Hot Gulab Jamun with Rabri', 'DESSERTS', 180.00, 0.0000, 0.0000, 0.0100],
            ['Zafrani Kheer Khaas', 'DESSERTS', 160.00, 0.0000, 0.0000, 0.0050],
            ['Mint Raita & Fresh Salad', 'SALADS', 80.00, 0.0000, 0.0000, 0.0000],
            ['Cold Drinks & Mineral Water', 'BEVERAGES', 90.00, 0.0000, 0.0000, 0.0000],
            ['Peshawari Green Tea / Kahwa', 'BEVERAGES', 60.00, 0.0000, 0.0000, 0.0000],
        ];

        foreach ($menuItems as $m) {
            MenuItem::create([
                'tenant_id' => $tenant->id,
                'name' => $m[0],
                'category' => $m[1],
                'unit_cost' => $m[2],
                'raw_meat_ratio_kg' => $m[3],
                'raw_rice_ratio_kg' => $m[4],
                'raw_oil_ratio_liters' => $m[5],
                'is_active' => true,
            ]);
        }

        // 7. Packages
        $pkgBarat = Package::create([
            'tenant_id' => $tenant->id,
            'name' => 'Royal Barat Grand Feast',
            'description' => 'Mutton Qorma, Chicken Biryani, Chicken Seekh Kababs, Hot Roghni Naan, Gulab Jamun, Salad & Raita.',
            'per_head_price' => 2450.00,
            'min_guests' => 150,
            'menu_items_json' => [
                'Starters' => ['Chicken Seekh Kabab', 'Mint Raita', 'Fresh Garden Salad'],
                'Mains' => ['Mutton Qorma Mughlai', 'Chicken Biryani Special', 'Fresh Roghni Naan'],
                'Dessert' => ['Hot Gulab Jamun with Rabri', 'Kashmiri Chai'],
                'Beverages' => ['Cold Drinks', 'Mineral Water'],
            ],
            'is_active' => true,
        ]);

        $pkgValima = Package::create([
            'tenant_id' => $tenant->id,
            'name' => 'Platinum Valima Buffet',
            'description' => 'Mutton Dum Pukht Pulao, Chicken Handi, Fish Fry, Kulcha, Zafrani Kheer, Mineral Water.',
            'per_head_price' => 2750.00,
            'min_guests' => 200,
            'menu_items_json' => [
                'Starters' => ['Crispy Fish Finger Bites', 'Russian Salad'],
                'Mains' => ['Mutton Dum Pukht Pulao', 'Chicken Makhni Handi', 'Garlic Naan'],
                'Dessert' => ['Zafrani Kheer Khaas', 'Seasonal Kulfi'],
                'Beverages' => ['Cold Drinks', 'Mineral Water', 'Green Tea'],
            ],
            'is_active' => true,
        ]);

        $pkgMehndi = Package::create([
            'tenant_id' => $tenant->id,
            'name' => 'Mehndi Festive Night Feast',
            'description' => 'Live BBQ Tikka Boti, Puri Chana, Halwa, Live Jalebi Counter, Mint Raita.',
            'per_head_price' => 1950.00,
            'min_guests' => 100,
            'menu_items_json' => [
                'Live Stations' => ['Chicken Tikka Boti', 'Puri Chana Halwa', 'Live Crispy Jalebi'],
                'Rice & Bread' => ['Chicken Pulao', 'Roghni Naan'],
                'Dessert' => ['Gulab Jamun', 'Doodh Patti Chai'],
            ],
            'is_active' => true,
        ]);

        // 8. Sample Bookings with Payments, KOT, Utility Log & RSVPs
        $today = Carbon::today()->format('Y-m-d');
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $dayAfter = Carbon::today()->addDays(2)->format('Y-m-d');

        // Booking 1: Today Evening Barat (Confirmed)
        $booking1 = Booking::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hallRoyal->id,
            'package_id' => $pkgBarat->id,
            'booking_reference' => 'VC-BK-202610-001',
            'created_by_user_id' => $frontDeskUser->id,
            'customer_name' => 'Chaudhry Muhammad Tariq Gujjar',
            'customer_phone' => '+92 300 8471122',
            'customer_cnic' => '35201-1234567-1',
            'customer_email' => 'tariq.gujjar@example.com',
            'event_type' => 'Barat',
            'event_date' => $today,
            'slot_type' => 'EVENING',
            'guest_count' => 500,
            'per_head_rate' => 2450.00,
            'hall_rent' => 150000.00,
            'addons_total' => 45000.00,
            'discount_amount' => 20000.00,
            'tax_amount' => 70000.00,
            'grand_total' => 1470000.00,
            'advance_paid' => 800000.00,
            'balance_due' => 670000.00,
            'status' => 'CONFIRMED',
            'notes' => 'VIP stage floral decoration with fresh white gladiolus and golden sofa lounge.',
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking1->id,
            'amount' => 500000.00,
            'payment_type' => 'TOKEN_ADVANCE',
            'payment_method' => 'CASH',
            'receipt_no' => 'WH-RCP-20261009-0001-A9F2',
            'received_by_user_id' => $frontDeskUser->id,
            'notes' => 'Token advance deposit received at front desk.',
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking1->id,
            'amount' => 300000.00,
            'payment_type' => 'SECOND_INSTALLMENT',
            'payment_method' => 'BANK_TRANSFER',
            'receipt_no' => 'WH-RCP-20261009-0002-K4B7',
            'transaction_reference' => 'HBL-FT-893019',
            'received_by_user_id' => $frontDeskUser->id,
            'notes' => '50% installment clearance before event date.',
        ]);

        KitchenKot::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking1->id,
            'kot_number' => 'KOT-261009-001',
            'guest_count' => 500,
            'raw_meat_kg' => 110.00,
            'raw_rice_kg' => 80.00,
            'raw_oil_liters' => 25.00,
            'dishes_summary' => $pkgBarat->menu_items_json,
            'preparation_status' => 'PREPARING',
            'custom_instructions' => 'Mutton should be tender Peshawari cut; Biryani extra aromatics with pure saffron.',
        ]);

        UtilityLog::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking1->id,
            'generator_run_hours' => 4.00,
            'diesel_liters_consumed' => 48.00,
            'diesel_rate_per_liter' => 280.00,
            'wapda_units_consumed' => 250.00,
            'wapda_unit_rate' => 65.00,
            'total_utility_cost' => 29690.00,
            'logged_by' => 'Usman Tariq',
            'notes' => 'Load shedding occurred from 8:00 PM to 11:30 PM. 500KVA Cat Generator engaged.',
        ]);

        // RSVPs for Booking 1
        GuestRsvp::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking1->id,
            'guest_name' => 'Mian Waqas Ahmad',
            'phone' => '+923001234567',
            'attending_count' => 4,
            'rsvp_status' => 'ATTENDING',
            'last_reply_message' => 'Attending: 4 people with family',
        ]);

        GuestRsvp::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking1->id,
            'guest_name' => 'Sardar Naveed Butt',
            'phone' => '+923219876543',
            'attending_count' => 2,
            'rsvp_status' => 'ATTENDING',
            'last_reply_message' => 'Yes, 2 guests confirming.',
        ]);

        GuestRsvp::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking1->id,
            'guest_name' => 'Dr. Salman Qureshi',
            'phone' => '+923335551122',
            'attending_count' => 0,
            'rsvp_status' => 'DECLINED',
            'last_reply_message' => 'Sorry, out of city so cannot attend.',
        ]);

        // Booking 2: Tomorrow Evening Valima
        $booking2 = Booking::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hallCrystal->id,
            'package_id' => $pkgValima->id,
            'booking_reference' => 'VC-BK-202610-002',
            'created_by_user_id' => $frontDeskUser->id,
            'customer_name' => 'Syed Hammad Ali Shah',
            'customer_phone' => '+92 312 9001234',
            'customer_cnic' => '35202-9876543-3',
            'customer_email' => 'hammad.shah@example.com',
            'event_type' => 'Valima',
            'event_date' => $tomorrow,
            'slot_type' => 'EVENING',
            'guest_count' => 650,
            'per_head_rate' => 2750.00,
            'hall_rent' => 220000.00,
            'addons_total' => 60000.00,
            'discount_amount' => 30000.00,
            'tax_amount' => 101875.00,
            'grand_total' => 2139375.00,
            'advance_paid' => 1000000.00,
            'balance_due' => 1139375.00,
            'status' => 'CONFIRMED',
            'notes' => 'Lawn walkway red carpet setup with cold fire sparkle entries.',
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking2->id,
            'amount' => 1000000.00,
            'payment_type' => 'TOKEN_ADVANCE',
            'payment_method' => 'BANK_TRANSFER',
            'receipt_no' => 'WH-RCP-20261009-0003-V7P1',
            'transaction_reference' => 'MEEZAN-TRX-102938',
            'received_by_user_id' => $frontDeskUser->id,
        ]);

        KitchenKot::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking2->id,
            'kot_number' => 'KOT-261009-002',
            'guest_count' => 650,
            'raw_meat_kg' => 143.00,
            'raw_rice_kg' => 104.00,
            'raw_oil_liters' => 32.50,
            'dishes_summary' => $pkgValima->menu_items_json,
            'preparation_status' => 'PENDING',
        ]);

        // Booking 3: Day after tomorrow Afternoon Mehndi
        $booking3 = Booking::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hallImperial->id,
            'package_id' => $pkgMehndi->id,
            'booking_reference' => 'VC-BK-202610-003',
            'created_by_user_id' => $frontDeskUser->id,
            'customer_name' => 'Malik Zubair Khan',
            'customer_phone' => '+92 345 7788990',
            'customer_cnic' => '35201-5544332-9',
            'customer_email' => 'zubair.khan@example.com',
            'event_type' => 'Mehndi',
            'event_date' => $dayAfter,
            'slot_type' => 'AFTERNOON',
            'guest_count' => 250,
            'per_head_rate' => 1950.00,
            'hall_rent' => 110000.00,
            'addons_total' => 25000.00,
            'discount_amount' => 10000.00,
            'tax_amount' => 30625.00,
            'grand_total' => 643125.00,
            'advance_paid' => 300000.00,
            'balance_due' => 343125.00,
            'status' => 'CONFIRMED',
            'notes' => 'Yellow marigold flower draping on stage and sound console for dholak.',
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking3->id,
            'amount' => 300000.00,
            'payment_type' => 'TOKEN_ADVANCE',
            'payment_method' => 'CASH',
            'receipt_no' => 'WH-RCP-20261009-0004-M8L3',
            'received_by_user_id' => $frontDeskUser->id,
        ]);

        KitchenKot::create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking3->id,
            'kot_number' => 'KOT-261009-003',
            'guest_count' => 250,
            'raw_meat_kg' => 55.00,
            'raw_rice_kg' => 40.00,
            'raw_oil_liters' => 12.50,
            'dishes_summary' => $pkgMehndi->menu_items_json,
            'preparation_status' => 'PENDING',
        ]);

        // 8. Operational Expenses for Vendor ERP
        Expense::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hallRoyal->id,
            'category' => 'GENERATOR_DIESEL',
            'title' => 'Caterpillar 150 kVA Diesel Refuel (180 Liters)',
            'amount' => 54000.00,
            'expense_date' => Carbon::now()->subDays(3)->format('Y-m-d'),
            'payee' => 'PSO Liberty Station',
            'payment_method' => 'CASH',
            'receipt_number' => 'PSO-REC-88910',
            'notes' => 'Diesel filled for weekend Barat load shedding backup.',
            'created_by_user_id' => $adminUser->id,
        ]);

        Expense::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hallCrystal->id,
            'category' => 'FRESH_FLORAL_DECOR',
            'title' => 'Fresh Red Rose & Baby Breath Stage Floral Arrangement',
            'amount' => 38000.00,
            'expense_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'payee' => 'Pattoki Flora Importers',
            'payment_method' => 'BANK_TRANSFER',
            'receipt_number' => 'FLR-INV-1102',
            'notes' => 'Decor procurement for VIP Barat entrance walkway and stage backdrop.',
            'created_by_user_id' => $frontDeskUser->id,
        ]);

        Expense::create([
            'tenant_id' => $tenant->id,
            'hall_id' => $hallRoyal->id,
            'category' => 'RAW_FOOD_MEAT',
            'title' => 'Fresh Mutton (120 KG) & Desi Chicken (80 KG)',
            'amount' => 245000.00,
            'expense_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
            'payee' => 'Al-Haj Meat Traders, Tollinton Market',
            'payment_method' => 'CASH',
            'receipt_number' => 'TLT-MT-4401',
            'notes' => 'Culinary meat supplies for 450-guest Imperial Barat function.',
            'created_by_user_id' => $chefUser->id,
        ]);

        Expense::create([
            'tenant_id' => $tenant->id,
            'hall_id' => null,
            'category' => 'WAPDA_ELECTRICITY',
            'title' => 'LESCO Commercial Electricity Bill (Demand 45 kW)',
            'amount' => 88500.00,
            'expense_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'payee' => 'LESCO Sub-Division 4',
            'payment_method' => 'BANK_TRANSFER',
            'receipt_number' => 'LESCO-202610-8912',
            'notes' => 'Monthly electricity demand charges and peak-hour units.',
            'created_by_user_id' => $adminUser->id,
        ]);

        // 9. System Audit Logs
        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $adminUser->id,
            'action' => 'SUBSCRIPTION_ACTIVATED',
            'entity_type' => 'Tenant',
            'entity_id' => $tenant->id,
            'description' => 'Tenant subscription [Executive Banquet Suite] activated for 12 months with verified business credentials.',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
