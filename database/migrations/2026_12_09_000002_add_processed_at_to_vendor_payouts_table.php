<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_payouts') || Schema::hasColumn('vendor_payouts', 'processed_at')) {
            return;
        }

        Schema::table('vendor_payouts', function (Blueprint $table) {
            $table->timestamp('processed_at')->nullable()->after('processed_by');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vendor_payouts') || ! Schema::hasColumn('vendor_payouts', 'processed_at')) {
            return;
        }

        Schema::table('vendor_payouts', function (Blueprint $table) {
            $table->dropColumn('processed_at');
        });
    }
};
