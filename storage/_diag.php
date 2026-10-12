<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$h = App\Models\Hall::with('tenant')->get(['id','name','tenant_id','is_active']);
foreach ($h as $x) {
    echo $x->id . ' | ' . $x->name
        . ' | active=' . var_export($x->is_active, true)
        . ' | tenant=' . ($x->tenant->name ?? 'NULL')
        . ' | tenant_status=' . ($x->tenant->verification_status ?? 'NULL')
        . ' | tenant_active=' . var_export($x->tenant->is_active ?? null, true)
        . PHP_EOL;
}
echo 'TOTAL HALLS: ' . $h->count() . PHP_EOL;
echo '---VISIBLE ON HOME (active hall + approved+active tenant)---' . PHP_EOL;
$vis = App\Models\Hall::where('is_active', true)
    ->whereHas('tenant', function ($q) {
        $q->where('is_active', true)->where('verification_status', 'APPROVED');
    })->get();
echo 'VISIBLE COUNT: ' . $vis->count() . PHP_EOL;

echo '--- USERS ---' . PHP_EOL;
foreach (App\Models\User::all() as $u) {
    echo $u->email . ' | role=' . $u->role
        . ' | tenant_id=' . ($u->tenant_id ?? 'NULL')
        . ' | tenant_name=' . ($u->tenant->name ?? 'NULL')
        . PHP_EOL;
}

