<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Use testing database
config(['database.default' => 'sqlite_testing']);
config(['database.connections.sqlite_testing.database' => database_path('testing.sqlite')]);

use App\Models\Tenant;
use App\Models\User;

$tenant = Tenant::where('email', 'tanveer@skyline.pk')->first();
echo 'Tenant: '.($tenant ? $tenant->name : 'NOT FOUND')."\n";

$user = User::where('email', 'tanveer@skyline.pk')->first();
echo 'User: '.($user ? $user->name : 'NOT FOUND')."\n";

// Check all tenants
$tenants = Tenant::all();
echo "All tenants:\n";
foreach ($tenants as $t) {
    echo "  - {$t->name} ({$t->email})\n";
}
