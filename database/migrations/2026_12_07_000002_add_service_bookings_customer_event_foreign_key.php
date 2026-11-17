<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the service_bookings → customer_events foreign key that could not be
 * declared inline: the referenced table is created in the immediately
 * following migration file (2026_12_01_000003). Declaring it inline made
 * `migrate` fail on MySQL/MariaDB (errno 150); SQLite never enforced it.
 *
 * SQLite cannot add foreign keys through ALTER TABLE, so enforcement is
 * skipped there (SQLite dev/test runs with FKs off by default anyway);
 * MySQL and PostgreSQL receive the real constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (! Schema::hasTable('service_bookings')
            || ! Schema::hasTable('customer_events')
            || ! Schema::hasColumn('service_bookings', 'customer_event_id')) {
            return;
        }

        Schema::table('service_bookings', function (Blueprint $table) {
            $table->foreign('customer_event_id')
                ->references('id')
                ->on('customer_events')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('service_bookings') && Schema::hasColumn('service_bookings', 'customer_event_id')) {
            Schema::table('service_bookings', function (Blueprint $table) {
                $table->dropForeign(['customer_event_id']);
            });
        }
    }
};
