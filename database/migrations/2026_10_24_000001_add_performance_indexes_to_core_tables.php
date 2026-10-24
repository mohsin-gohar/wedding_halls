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
        if (Schema::hasTable('halls')) {
            Schema::table('halls', function (Blueprint $table) {
                if (Schema::hasColumn('halls', 'slug') && ! Schema::hasIndex('halls', ['slug'])) {
                    $table->index('slug', 'idx_halls_slug');
                }
                if (Schema::hasColumn('halls', 'is_active') && ! Schema::hasIndex('halls', ['is_active'])) {
                    $table->index('is_active', 'idx_halls_is_active');
                }
            });
        }

        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                if (Schema::hasColumn('tenants', 'verification_status') && ! Schema::hasIndex('tenants', ['verification_status'])) {
                    $table->index('verification_status', 'idx_tenants_verification_status');
                }
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasColumn('bookings', 'event_date') && ! Schema::hasIndex('bookings', ['event_date'])) {
                    $table->index('event_date', 'idx_bookings_event_date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('halls')) {
            Schema::table('halls', function (Blueprint $table) {
                if (Schema::hasIndex('halls', 'idx_halls_slug')) {
                    $table->dropIndex('idx_halls_slug');
                }
                if (Schema::hasIndex('halls', 'idx_halls_is_active')) {
                    $table->dropIndex('idx_halls_is_active');
                }
            });
        }

        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                if (Schema::hasIndex('tenants', 'idx_tenants_verification_status')) {
                    $table->dropIndex('idx_tenants_verification_status');
                }
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasIndex('bookings', 'idx_bookings_event_date')) {
                    $table->dropIndex('idx_bookings_event_date');
                }
            });
        }
    }
};
