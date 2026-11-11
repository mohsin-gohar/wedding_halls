<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SubscriptionPlan;

$plan = SubscriptionPlan::where('slug', 'starter-marquee')->first();
var_dump($plan);
