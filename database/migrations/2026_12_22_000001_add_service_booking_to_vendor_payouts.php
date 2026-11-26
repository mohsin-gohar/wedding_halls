<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link auto-requested vendor payouts to their source booking so a cash
     * refund can release the reservation instead of paying out a cancelled job.
     * Additive only: no historical payout value or reference is rewritten.
     */
    public function up(): void
    {
        if (! Schema::hasTable('vendor_payouts') || Schema::hasColumn('vendor_payouts', 'service_booking_id')) {
            return;
        }

        Schema::table('vendor_payouts', function (Blueprint $table): void {
            $table->foreignUuid('service_booking_id')->nullable()->after('tenant_id')
                ->constrained('service_bookings')->nullOnDelete();
            $table->index(['service_booking_id', 'status'], 'vendor_payouts_booking_status_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vendor_payouts') || ! Schema::hasColumn('vendor_payouts', 'service_booking_id')) {
            return;
        }

        Schema::table('vendor_payouts', function (Blueprint $table): void {
            $table->dropIndex('vendor_payouts_booking_status_idx');
            $table->dropConstrainedForeignId('service_booking_id');
        });
    }
};
