<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Enforce one authoritative KOT per booking without touching historical rows.
 *
 * Deployed databases may contain legacy duplicate tickets. This migration is
 * additive and non-destructive: it adds the uniqueness constraint only when
 * the existing table is already clean. If duplicates are found, the index is
 * deliberately skipped so no historical record is rewritten or deleted; the
 * duplicated rows must be resolved by an authorised data-owner decision.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kitchen_kots')) {
            return;
        }

        $index = 'kitchen_kots_booking_id_unique';

        if (Schema::hasIndex('kitchen_kots', $index, 'unique')) {
            return;
        }

        $duplicate = DB::table('kitchen_kots')
            ->select('booking_id', DB::raw('COUNT(*) as total'))
            ->groupBy('booking_id')
            ->having('total', '>', 1)
            ->first();

        if ($duplicate) {
            Log::warning('Skipped unique KOT booking index because legacy duplicates exist.', [
                'table' => 'kitchen_kots',
                'index' => $index,
                'booking_id' => $duplicate->booking_id,
                'duplicate_count' => (int) $duplicate->total,
            ]);

            return;
        }

        Schema::table('kitchen_kots', function (Blueprint $table) use ($index): void {
            $table->unique('booking_id', $index);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('kitchen_kots')
            && Schema::hasIndex('kitchen_kots', 'kitchen_kots_booking_id_unique', 'unique')) {
            Schema::table('kitchen_kots', function (Blueprint $table): void {
                $table->dropUnique('kitchen_kots_booking_id_unique');
            });
        }
    }
};
