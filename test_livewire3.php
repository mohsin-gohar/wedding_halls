<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Livewire\Auth\Register;
use Livewire\Livewire;

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

// Check logs
echo "\n--- Logs ---\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    echo $logs;
}
