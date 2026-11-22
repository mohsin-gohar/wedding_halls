<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Make a customer's cash-payment reference an idempotency key per booking.
 *
 * Additive and non-destructive: historical rows are never rewritten or deleted.
 * If a deployed database already has duplicate (booking, reference) pairs the
 * index is skipped and logged, leaving that data decision to its owner.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_payments')) {
            return;
        }

        $index = 'service_payments_booking_reference_unique';

        if (Schema::hasIndex('service_payments', $index, 'unique')) {
            return;
        }

        $duplicate = DB::table('service_payments')
            ->select('service_booking_id', 'reference', DB::raw('COUNT(*) as total'))
            ->whereNotNull('reference')
            ->groupBy('service_booking_id', 'reference')
            ->having('total', '>', 1)
            ->first();

        if ($duplicate) {
            Log::warning('Skipped service payment idempotency index because legacy duplicates exist.', [
                'table' => 'service_payments',
                'index' => $index,
                'service_booking_id' => $duplicate->service_booking_id,
                'reference' => $duplicate->reference,
                'duplicate_count' => (int) $duplicate->total,
            ]);

            return;
        }

        Schema::table('service_payments', function (Blueprint $table) use ($index): void {
            $table->unique(['service_booking_id', 'reference'], $index);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('service_payments')
            && Schema::hasIndex('service_payments', 'service_payments_booking_reference_unique', 'unique')) {
            Schema::table('service_payments', function (Blueprint $table): void {
                $table->dropUnique('service_payments_booking_reference_unique');
            });
        }
    }
};
