<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Use testing database
config(['database.connections.sqlite.database' => 'database/testing.sqlite']);
config(['database.default' => 'sqlite']);

use App\Livewire\Auth\Register;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// Run the seeder first
DB::statement('DELETE FROM subscription_plans');
DB::statement('DELETE FROM tenants');
DB::statement('DELETE FROM users');
DB::statement('DELETE FROM venues');
DB::statement('DELETE FROM halls');

Artisan::call('db:seed', ['--force' => true]);

$component = Livewire::test(Register::class)
    ->set('hallName', 'Skyline Grand Marquee')
    ->set('ownerName', 'Malik Tanveer')
    ->set('email', 'tanveer@skyline.pk')
    ->set('phone', '+92 300 7776655')
    ->set('city', 'Islamabad')
    ->set('capacity', 600)
    ->set('baseRent', 190000)
    ->set('password', 'secret123')
    ->set('password_confirmation', 'secret123')
    ->call('register');

echo 'Errors: ';
print_r($component->errors()->toArray());

echo 'Redirect: ';
var_dump($component->getRedirectUrl());

// Check if tenant was created
use App\Models\Tenant;

$tenant = Tenant::where('email', 'tanveer@skyline.pk')->first();
echo 'Tenant found: '.($tenant ? $tenant->name : 'NOT FOUND')."\n";
