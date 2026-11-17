<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_policies', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_policies', 'default_basic_salary')) {
                $table->decimal('default_basic_salary', 12, 2)->nullable()->after('unpaid_leave_deduction_per_day');
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'shift_windows')) {
                $table->json('shift_windows')->nullable()->after('weekly_off_days');
            }
            if (! Schema::hasColumn('tenants', 'shift_buffer_minutes')) {
                $table->unsignedSmallInteger('shift_buffer_minutes')->default(0)->after('shift_windows');
            }
            if (! Schema::hasColumn('tenants', 'hold_expiry_hours')) {
                $table->unsignedSmallInteger('hold_expiry_hours')->nullable()->after('shift_buffer_minutes');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'hold_expires_at')) {
                $table->timestamp('hold_expires_at')->nullable()->after('status');
                $table->index(['status', 'hold_expires_at'], 'idx_bookings_hold_expiry');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'hold_expires_at')) {
                $table->dropIndex('idx_bookings_hold_expiry');
                $table->dropColumn('hold_expires_at');
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            foreach (['hold_expiry_hours', 'shift_buffer_minutes', 'shift_windows'] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('payroll_policies', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_policies', 'default_basic_salary')) {
                $table->dropColumn('default_basic_salary');
            }
        });
    }
};
