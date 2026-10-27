<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'hall_id') && ! Schema::hasIndex('bookings', ['hall_id'])) {
                $table->index('hall_id', 'idx_bookings_hall_id');
            }
            if (Schema::hasColumn('bookings', 'tenant_id') && ! Schema::hasIndex('bookings', ['tenant_id'])) {
                $table->index('tenant_id', 'idx_bookings_tenant_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasIndex('bookings', 'idx_bookings_hall_id')) {
                $table->dropIndex('idx_bookings_hall_id');
            }
            if (Schema::hasIndex('bookings', 'idx_bookings_tenant_id')) {
                $table->dropIndex('idx_bookings_tenant_id');
            }
        });
    }
};
