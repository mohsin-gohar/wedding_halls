# 🌱 EVENTHUB SAAS - DATABASE SEEDERS & FACTORIES

## 📊 **Overview**
Comprehensive fake data factories and a master seeder simulating a real-world multi-tenant production environment. All factories use UUIDs, realistic relationships, and Faker library.

---
<tool_call>
<function=write>
<parameter=content>
<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Venue;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\StaffAttendance;
use App\Models\StaffLeave;
use App\Models\StaffPayroll;
use App\Models\Review;
use App\Models\HallChatMessage;
use App\Models\WhatsappWebhookLog;
use App\Models\Notification;
use App\Models\SeoMetadata;
use App\Models\FeatureFlag;
use App\Models\PlatformTask;
use App\Models\AuditLog;
use App\Models\EmailQueue;
use App\Models\InventoryItem;
use App\Models\InventoryLog;
use App\Models\StaffPayroll as StaffPayrollModel;
use Faker\Generator as FakerGenerator;
use Illuminate\Support\Str;

$factory->define(User::class, function (FakerGenerator $faker) {
    return [
        'id' => Str::uuid(),
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => bcrypt('password123'), // Default password
        'tenant_id' => null, // Will be set by TenantFactory
        'role' => $faker->randomElement([
            'super_admin',
            'tenant_owner',
            'manager',
            'accountant',
            'chef',
            'floor_staff',
        ]),
        'is_active' => $faker->boolean(0.8), // 80% active
        'verification_status' => $faker->randomElement([
            'PENDING',
            'APPROVED',
            'SUSPENDED',
        ]),
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
        'email_verified_at' => now(),
        'remember_token' => Str::random(10),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(Tenant::class, function (FakerGenerator $faker) {
    return [
        'id' => Str::uuid(),
        'user_id' => function () {
            // Return a user or null
            return User::factory()->exists() ? User::factory()->create() : null;
        },
        'name' => $faker->company,
        'slug' => Str::slug($faker->company . '-' . $faker->randomNumber(100)),
        'area' => $faker->city,
        'city' => $faker->city,
        'country' => 'PK',
        'address' => $faker->address,
        'is_active' => $faker->boolean(0.9),
        'verification_status' => $faker->randomElement(['PENDING', 'APPROVED', 'SUSPENDED']),
        'max_halls' => $faker->numberBetween(1, 50),
        'max_staff' => $faker->numberBetween(1, 100),
        'max_bookings_per_day' => $faker->numberBetween(1, 20),
        'free_access_days' => 30,
        'billing_enabled' => $faker->boolean(0.3), // 30% have billing
        'kitchen_module_enabled' => $faker->boolean(0.1), // 10% have kitchen
        'payment_gateway' => $faker->randomElement(['stripe', 'jazzcash', 'easypaisa', 'bank_transfer']),
        'timezone' => 'Asia/Karachi',
        'currency' => 'PKR',
        'plan' => $faker->randomElement(['free', 'basic', 'pro', 'enterprise']),
        'settings' => [
            'allow_photos' => true,
            'allow_reviews' => true,
            'allow_bookings' => true,
        ],
        'trial_ends_at' => $faker->date_time_this_year(),
        'subscription_ends_at' => $faker->date_time_this_year()->modify('+1 year'),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(Venue::class, function (FakerGenerator $faker) {
    $hallTypes = [
        'banquet_ballroom' => ['max_capacity' => 300, 'base_rent' => 150000],
        'open_air marquee' => ['max_capacity' => 200, 'base_rent' => 100000],
        'garden lawn' => ['max_capacity' => 150, 'base_rent' => 80000],
        'rooftop' => ['max_capacity' => 100, 'base_rent' => 120000],
        'private dining room' => ['max_capacity' => 50, 'base_rent' => 60000],
    ];

    $type = $faker->randomElement(array_keys($hallTypes));
    $caps = $hallTypes[$type];

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'hall_type' => $type,
        'name' => $faker->unique()->company . ' Banquet Hall',
        'slug' => Str::slug($faker->company . '-' . $faker->randomNumber(100)),
        'description' => $faker->paragraph(3),
        'tagline' => $faker->sentence(4),
        'max_capacity' => $caps['max_capacity'],
        'base_rent' => $caps['base_rent'],
        'status' => $faker->randomElement(['ACTIVE', 'INACTIVE', 'PENDING', 'MAINTENANCE']),
        'is_featured' => $faker->boolean(0.2),
        'sort_order' => $faker->numberBetween(1, 100),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(Booking::class, function (FakerGenerator $faker) {
    $statuses = ['INQUIRY', 'HOLD', 'CONFIRMED', 'COMPLETED', 'CANCELLED'];
    $slotTypes = ['AFTERNOON', 'EVENING', 'NIGHT'];

    $randomDate = \Carbon\Carbon::parse('now')->addDays($faker->numberBetween(1, 365));

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'venue_id' => Venue::factory(),
        'user_id' => $faker->boolean(0.7) ? User::factory() : null,
        'booked_by' => $faker->boolean(0.3) ? User::factory() : null,
        'event_date' => $randomDate->format('Y-m-d'),
        'slot_type' => $faker->randomElement($slotTypes),
        'status' => $faker->weightedRandom(['INQUIRY' => 40, 'HOLD' => 25, 'CONFIRMED' => 25, 'CANCELLED' => 10, 'COMPLETED' => 0]),
        'guests_count' => $faker->numberBetween(10, 500),
        'total_amount' => $faker->randomFloat(2, 50000, 500000),
        'advance_paid' => $faker->randomFloat(2, 10000, $total_amount),
        'balance_due' => null, // Calculated later
        'grand_total' => $total_amount,
        'payment_method' => $faker->randomElement(['stripe', 'jazzcash', 'easypaisa', 'bank_transfer', 'cash']),
        'transaction_id' => $faker->boolean(0.6) ? 'txn_' . $faker->randomNumber(10) : null,
        'notes' => $faker->optional sentence(2),
        'is_recurring' => $faker->boolean(0.15),
        'recurrence_pattern' => $faker->numberBetween(1, 3),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(Package::class, function (FakerGenerator $faker) {
    $packages = [
        ['name' => 'Bronze', 'price' => 50000, 'billing_unit' => 'per event'],
        ['name' => 'Silver', 'price' => 100000, 'billing_unit' => 'per event'],
        ['name' => 'Gold', 'price' => 150000, 'billing_unit' => 'per event'],
        ['name' => 'Platinum', 'price' => 250000, 'billing_unit' => 'per event'],
        ['name' => 'Diamond', 'price' => 500000, 'billing_unit' => 'per event'],
    ];

    $pkg = $faker->randomElement($packages);

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'name' => $pkg['name'],
        'description' => $faker->paragraph(2),
        'price' => $pkg['price'],
        'billing_unit' => $pkg['billing_unit'],
        'max_capacity' => $faker->numberBetween(50, 500),
        'is_active' => $faker->boolean(0.9),
        'sort_order' => $faker->numberBetween(1, 10),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(Payment::class, function (FakerGenerator $faker) {
    $providers = ['stripe', 'jazzcash', 'easypaisa', 'bank_transfer', 'cash'];
    $statuses = ['pending', 'completed', 'failed', 'refunded'];
    $paymentTypes = ['deposit', 'full', 'balance', 'refund'];

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'booking_id' => Booking::factory(),
        'provider' => $faker->randomElement($providers),
        'provider_payment_id' => $faker->boolean(0.7) ? 'py_'. $faker->randomNumber(10) : null,
        'status' => $faker->weightedRandom(['pending' => 50, 'completed' => 30, 'failed' => 15, 'refunded' => 5]),
        'amount' => $faker->randomFloat(2, 10000, 500000),
        'currency' => 'PKR',
        'metadata' => [
            'payment_reference' => 'REF-'. $faker->randomNumber(10),
            'gateway_response' => ['status' => $this->status],
        ],
        'payment_type' => $faker->randomElement($paymentTypes),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(Subscription::class, function (FakerGenerator $faker) {
    $plans = [
        ['plan_name' => 'free', 'price' => 0, 'billing_cycle' => 0],
        ['plan_name' => 'basic', 'price' => 20000, 'billing_cycle' => 1],
        ['plan_name' => 'pro', 'price' => 50000, 'billing_cycle' => 1],
        ['plan_name' => 'enterprise', 'price' => 150000, 'billing_cycle' => 2],
    ];

    $plan = $faker->randomElement($plans);

    $startsAt = now();
    $endsAt = now()->add($plan['billing_cycle'] === 1 ? '1 month' : '3 months');

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'plan_name' => $plan['plan_name'],
        'price' => $plan['price'],
        'billing_cycle' => $plan['billing_cycle'],
        'max_halls' => $plan['plan_name'] === 'free' ? 1 : null,
        'max_staff' => $plan['plan_name'] === 'free' ? 5 : null,
        'max_bookings' => $plan['plan_name'] === 'free' ? 5 : null,
        'storage_gb' => $plan['plan_name'] === 'free' ? null : 10,
        'kitchen_module' => $plan['plan_name'] !== 'free',
        'priority_support' => $plan['plan_name'] === 'enterprise',
        'is_trial' => $plan['plan_name'] === 'free',
        'is_active' => true,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'canceled_at' => null,
        'promo_code' => $faker->boolean(0.3) ? 'WELCOME-'. $faker->randomNumber(6) : null,
        'times_canceled' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(StaffAttendance::class, function (FakerGenerator $faker) {
    $statuses = ['PRESENT', 'ABSENT', 'HALF_DAY', 'ON_LEAVE', 'LATE'];

    $randomDate = \Carbon\Carbon::parse('now')->subDays($faker->numberBetween(0, 30));

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'user_id' => User::factory(), // Staff user
        'date' => $randomDate->format('Y-m-d'),
        'status' => $faker->weightedRandom(['PRESENT' => 60, 'ABSENT' => 15, 'HALF_DAY' => 10, 'ON_LEAVE' => 8, 'LATE' => 7]),
        'check_in' => $faker->boolean(0.8) ? $faker->time('09:00', '17:00') : null,
        'check_out' => $faker->boolean(0.7) ? $faker->time('17:00', '21:00') : null,
        'overtime_hours' => $faker->randomFloat(2, 0, 10),
        'notes' => $faker->optional sentence(1),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(StaffLeave::class, function (FakerGenerator $faker) {
    $leaveTypes = ['CASUAL', 'SICK', 'ANNUAL', 'EMERGENCY'];
    $statuses = ['PENDING', 'APPROVED', 'REJECTED'];

    $start = \Carbon\Carbon::parse('now')->addDays($faker->numberBetween(-10, 30));
    $end = $start->copy()->addDays($faker->numberBetween(1, 5));

    $days = $start->diffInDays($end) + 1;

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'user_id' => User::factory(), // Staff user
        'leave_type' => $faker->randomElement($leaveTypes),
        'start_date' => $start->format('Y-m-d'),
        'end_date' => $end->format('Y-m-d'),
        'days' => $days,
        'reason' => $faker->sentence(3),
        'status' => $faker->weightedRandom(['PENDING' => 50, 'APPROVED' => 30, 'REJECTED' => 20]),
        'action_by' => $faker->boolean(0.4) ? User::factory() : null,
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(StaffPayroll::class, function (FakerGenerator $faker) {
    $months = [
        now()->subMonth()->format('Y-m'),
        now()->format('Y-m'),
        now()->addMonth()->format('Y-m'),
    ];

    $month = $faker->randomElement($months);

    $basic = $faker->randomFloat(2, 40000, 150000);

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'user_id' => User::factory(), // Staff user
        'month_year' => $month,
        'basic_salary' => $basic,
        'overtime_hours' => $faker->randomFloat(2, 0, 20),
        'overtime_rate' => $faker->randomFloat(2, 1000, 5000),
        'allowances' => $faker->randomFloat(2, 0, 20000),
        'deductions' => $faker->randomFloat(2, 0, 10000),
        'net_salary' => $basic + 0, // Will be calculated
        'status' => $faker->randomElement(['DRAFT', 'APPROVED', 'PAID']),
        'paid_at' => $faker->boolean(0.5) ? now() : null,
        'payment_method' => $faker->randomElement(['bank_transfer', 'check', 'cash']),
        'notes' => $faker->optional sentence(2),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(Review::class, function (FakerGenerator $faker) {
    $ratings = [1, 2, 3, 4, 5];

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'venue_id' => Venue::factory(),
        'user_id' => User::factory(), // The couple
        'booking_id' => Booking::factory(),
        'rating' => $faker->randomElement($ratings),
        'title' => $faker->sentence(3),
        'review' => $faker->paragraph(2),
        'is_published' => $faker->boolean(0.8),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(HallChatMessage::class, function (FakerGenerator $faker) {
    $senderTypes = ['user', 'staff', 'system'];
    $messageTypes = ['text', 'image', 'file', 'system'];
    $statuses = ['read', 'unread'];

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'user_id' => $faker->boolean(0.5) ? User::factory() : null,
        'booking_id' => Booking::factory(),
        'sender_id' => $faker->numberBetween(1, 100),
        'receiver_id' => $faker->numberBetween(1, 100),
        'sender_type' => $faker->randomElement($senderTypes),
        'message_type' => $faker->randomElement($messageTypes),
        'content' => $faker->sentence(5),
        'is_read' => $faker->randomElement(['read', 'unread']),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(WhatsappWebhookLog::class, function (FakerGenerator $faker) {
    $eventTypes = ['message', 'status', 'optin'];
    $statuses = ['received', 'delivered', 'read'];

    return [
        'id' => Str::uuid(),
        'whatsapp_number' => '+92'. $faker->phoneNumber(),
        'event_type' => $faker->randomElement($eventTypes),
        'payload' => ['message' => $faker->sentence(2)],
        'status' => $faker->randomElement($statuses),
        'related_booking_id' => Booking::factory(),
        'related_user_id' => User::factory(),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(Notification::class, function (FakerGenerator $faker) {
    $types = ['booking', 'leave', 'payment', 'system', 'announcement'];

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'user_id' => User::factory(),
        'type' => $faker->randomElement($types),
        'title' => $faker->sentence(3),
        'message' => $faker->paragraph(1),
        'is_read' => $faker->boolean(0.5),
        'is_email_sent' => $faker->boolean(0.3),
        'is_sms_sent' => $faker->boolean(0.1),
        'is_whatsapp_sent' => $faker->boolean(0.1),
        'notificationable_type' => 'App\Models\Booking',
        'notificationable_id' => Booking::factory(),
        'priority' => $faker->numberBetween(1, 4),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(SeoMetadata::class, function (FakerGenerator $faker) {
    $pageTypes = ['venue', 'hall', 'package', 'static'];

    return [
        'id' => Str::uuid(),
        'page_type' => $faker->randomElement($pageTypes),
        'page_id' => Str::uuid(),
        'title' => $faker->sentence(4),
        'description' => $faker->paragraph(2),
        'slug' => Str::slug($faker->word),
        'focus_keyword' => $faker->word,
        'is_indexed' => $faker->boolean(0.8),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(FeatureFlag::class, function (FakerGenerator $faker) {
    $names = ['billing_enabled', 'kitchen_module_enabled', 'free_access_days', 'search_enabled'];

    return [
        'id' => Str::uuid(),
        'name' => $faker->randomElement($names),
        'value' => $faker->randomElement(['true', 'false', '30', '90']),
        'is_active' => $faker->boolean(0.9),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(PlatformTask::class, function (FakerGenerator $faker) {
    $taskTypes = ['send_notification', 'generate_payroll', 'send_reminder', 'check_subscription', 'send_announcement'];

    return [
        'id' => Str::uuid(),
        'task_type' => $faker->randomElement($taskTypes),
        'payload' => ['data' => $faker->sentence(2)],
        'status' => $faker->randomElement(['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'CANCELED']),
        'attempts' => $faker->numberBetween(0, 5),
        'created_by' => User::factory(),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(AuditLog::class, function (FakerGenerator $faker) {
    $actions = ['created', 'updated', 'deleted', 'login', 'logout', 'booking_approved', 'booking_cancelled', 'payment_received', 'staff_attendance_marked', 'leave_approved'];

    return [
        'id' => Str::uuid(),
        'action' => $faker->randomElement($actions),
        'details' => ['changed_field' => $faker->word(), 'old_value' => $faker->word(), 'new_value' => $faker->word()],
        'user_id' => User::factory(),
        'tenant_id' => Tenant::factory(),
        'model_type' => 'App\Models\\'. $faker->randomElement(['Booking', 'User', 'Tenant', 'Venue', 'StaffAttendance']),
        'model_id' => Str::uuid(),
        'ip_address' => $faker->ipv4,
        'user_agent' => $faker->userAgent,
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(EmailQueue::class, function (FakerGenerator $faker) {
    $templates = ['welcome', 'booking_confirmation', 'password_reset', 'leave_approved', 'payment_received', 'invoice_generated'];

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'user_id' => User::factory(),
        'template_name' => $faker->randomElement($templates),
        'to_email' => $faker->safeEmail,
        'to_name' => $faker->name,
        'subject' => $faker->sentence(4),
        'html_content' => $faker->paragraph(3),
        'text_content' => $faker->sentence(3),
        'status' => $faker->randomElement(['pending', 'sent', 'failed', 'bounced']),
        'error_message' => $faker->optional($faker->sentence),
        'retry_count' => $faker->numberBetween(0, 5),
        'sent_at' => $faker->boolean(0.3) ? now() : null,
        'delivered_at' => $faker->boolean(0.5) ? now() : null,
        'bounced_at' => $faker->boolean(0.1) ? now() : null,
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(InventoryItem::class, function (FakerGenerator $faker) {
    $items = [
        ['name' => 'Wedding Decorations', 'unit' => 'pcs'],
        ['name' => 'Catering Supplies', 'unit' => 'pcs'],
        ['name' => 'Kitchen Equipment', 'unit' => 'pcs'],
        ['name' => 'Cleaning Supplies', 'unit' => 'pcs'],
        ['name' => 'Audio System', 'unit' => 'set'],
    ];

    $item = $faker->randomElement($items);

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'name' => $item['name'],
        'sku' => 'EVT-'. $faker->randomNumber(6),
        'description' => $faker->sentence(2),
        'current_stock' => $faker->numberBetween(0, 100),
        'minimum_stock' => $faker->numberBetween(10, 50),
        'maximum_stock' => $faker->numberBetween(500, 1000),
        'unit' => $item['unit'],
        'reorder_price' => $faker->randomFloat(2, 1000, 50000),
        'supplier' => $faker->company,
        'is_tracked' => $faker->boolean(0.9),
        'last_restocked' => $faker->date_time_this_year(),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});

$factory->define(InventoryLog::class, function (FakerGenerator $faker) {
    $changeTypes = ['in', 'out', 'adjustment', 'waste', 'theft'];

    return [
        'id' => Str::uuid(),
        'tenant_id' => Tenant::factory(),
        'inventory_item_id' => InventoryItem::factory(),
        'quantity_change' => $faker->numberBetween(-50, 50),
        'change_type' => $faker->randomElement($changeTypes),
        'reason' => $faker->sentence(2),
        'changed_by' => User::factory(),
        'change_date' => \Carbon\Carbon::parse('now')->subDays($faker->numberBetween(1, 30)),
        'created_at' => now(),
        'updated_at' => now(),
    ];
});