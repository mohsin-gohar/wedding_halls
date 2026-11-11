<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Hall;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$defaultPlan = SubscriptionPlan::where('slug', 'starter-marquee')->first()
    ?? SubscriptionPlan::where('is_active', true)->orderBy('price_monthly', 'asc')->first();

echo 'Default plan: '.($defaultPlan ? $defaultPlan->name : 'none')."\n";

$hallName = 'Skyline Grand Marquee';
$ownerName = 'Malik Tanveer';
$email = 'tanveer@skyline.pk';
$phone = '+92 300 7776655';
$city = 'Islamabad';
$capacity = 600;
$baseRent = 190000;
$password = 'secret123';

try {
    $user = DB::transaction(function () use ($hallName, $ownerName, $email, $phone, $city, $capacity, $baseRent, $password, $defaultPlan) {
        // 1. Create Tenant
        $slug = Str::slug($hallName).'-'.Str::lower(Str::random(5));

        $tenant = Tenant::create([
            'name' => $hallName,
            'slug' => $slug,
            'email' => $email,
            'phone' => $phone,
            'address' => $city.', Pakistan',
            'city' => $city,
            'subscription_plan' => $defaultPlan?->name ?? 'Starter Marquee',
            'subscription_plan_id' => $defaultPlan?->id,
            'subscription_status' => 'TRIAL',
            'billing_cycle' => 'MONTHLY',
            'subscription_starts_at' => now(),
            'subscription_expires_at' => now()->addDays(30),
            'verification_status' => 'PENDING',
            'currency' => 'PKR',
            'is_active' => true,
        ]);

        echo 'Tenant created: '.$tenant->id."\n";

        // 2. Create Venue
        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => $hallName.' Complex',
            'slug' => Str::slug($hallName.'-complex'),
            'city' => $city,
            'address' => $city.', Pakistan',
            'phone' => $phone,
            'email' => $email,
            'is_active' => true,
        ]);

        echo 'Venue created: '.$venue->id."\n";

        // 3. Create Hall
        $hall = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => $hallName,
            'min_capacity' => (int) ($capacity * 0.4),
            'max_capacity' => $capacity,
            'base_rent' => $baseRent,
            'description' => 'Luxury banqueting hall space with central climate control, power backup, and VIP bridal room.',
            'cover_image' => null,
            'images' => [],
            'videos' => [],
            'has_air_conditioning' => true,
            'has_generator_backup' => true,
            'has_bridal_room' => true,
            'is_active' => true,
        ]);

        echo 'Hall created: '.$hall->id."\n";

        // 4. Create User
        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => $ownerName,
            'email' => $email,
            'phone' => $phone,
            'role' => 'ROLE_TENANT_OWNER',
            'password' => Hash::make($password),
        ]);

        echo 'User created: '.$owner->id."\n";

        return $owner;
    });

    echo "Transaction completed successfully!\n";
    echo 'User ID: '.$user->id."\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    echo 'Trace: '.$e->getTraceAsString()."\n";
}
