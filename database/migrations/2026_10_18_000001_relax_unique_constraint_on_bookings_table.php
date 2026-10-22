<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // In SQLite tests, the initial table migration already uses a non-unique index.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        try {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropUnique('uq_hall_event_slot');
            });
        } catch (Throwable $e) {
            // Ignore if index is not present
        }

        try {
            Schema::table('bookings', function (Blueprint $table) {
                $table->index(['hall_id', 'event_date', 'slot_type'], 'idx_hall_event_slot');
            });
        } catch (Throwable $e) {
            // Ignore if index already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback
    }
};
