<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Additive backfill: historical utility logs without a linked expense get
     * one CASH expense row for reconciliation. Original amounts/rows are never
     * modified; new utility writes already create the link atomically.
     */
    public function up(): void
    {
        if (! Schema::hasTable('utility_logs') || ! Schema::hasTable('expenses')
            || ! Schema::hasColumn('utility_logs', 'expense_id')) {
            return;
        }

        DB::table('utility_logs')
            ->whereNull('expense_id')
            ->orderBy('id')
            ->chunkById(100, function ($logs): void {
                foreach ($logs as $log) {
                    $total = round((float) ($log->total_utility_cost ?? 0), 2);
                    if ($total <= 0) {
                        continue;
                    }

                    $expenseId = (string) Str::uuid();
                    DB::table('expenses')->insert([
                        'id' => $expenseId,
                        'tenant_id' => $log->tenant_id,
                        'category' => ((float) ($log->diesel_liters_consumed ?? 0) > 0)
                            ? 'GENERATOR_DIESEL' : 'WAPDA_ELECTRICITY',
                        'title' => 'Historical utility reconciliation',
                        'amount' => $total,
                        'expense_date' => optional($log->created_at)->format('Y-m-d') ?? now()->toDateString(),
                        'payment_method' => 'CASH',
                        'notes' => 'Backfilled link from utility log '.$log->id.'. Original values unchanged.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('utility_logs')->where('id', $log->id)->update(['expense_id' => $expenseId]);
                }
            });
    }

    public function down(): void
    {
        // Deliberately irreversible: backfilled expenses are financial history.
    }
};
