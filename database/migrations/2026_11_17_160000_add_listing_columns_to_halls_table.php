<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace listing contract for halls.
 *
 * These three columns were previously only present in some local SQLite
 * databases (schema drift created outside migrations). The public marketplace
 * queries them (`PublicVenues`, `PublicHallDetails`), so:
 *  - MySQL/MariaDB failed with "Unknown column 'listing_status'"
 *  - SQLite silently degraded to a tautology because a double-quoted unknown
 *    identifier falls back to a string literal ("listing_status" != 'WITHDRAWN'
 *    is always true) — the withdrawal filter never actually filtered.
 *
 * This migration makes the contract explicit and identical on every driver.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('halls')) {
            return;
        }

        Schema::table('halls', function (Blueprint $table) {
            if (! Schema::hasColumn('halls', 'listing_status')) {
                $table->string('listing_status', 20)->default('LISTED')->index()->after('is_active');
            }
            if (! Schema::hasColumn('halls', 'listing_purpose')) {
                $table->string('listing_purpose', 30)->nullable()->after('listing_status');
            }
            if (! Schema::hasColumn('halls', 'sale_price')) {
                $table->decimal('sale_price', 12, 2)->nullable()->after('listing_purpose');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('halls')) {
            return;
        }

        Schema::table('halls', function (Blueprint $table) {
            foreach (['listing_status', 'listing_purpose', 'sale_price'] as $column) {
                if (Schema::hasColumn('halls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
