<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the optional minimum-guest attribute used by MenuItem::meetsMinGuestCount().
     * Nullable with no default so existing historical rows are unchanged.
     */
    public function up(): void
    {
        if (! Schema::hasTable('menu_items') || Schema::hasColumn('menu_items', 'min_guest_count')) {
            return;
        }

        Schema::table('menu_items', function (Blueprint $table) {
            $table->unsignedInteger('min_guest_count')->nullable()->after('raw_oil_ratio_liters');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('menu_items') && Schema::hasColumn('menu_items', 'min_guest_count')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->dropColumn('min_guest_count');
            });
        }
    }
};
