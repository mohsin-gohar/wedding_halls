<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_payrolls', function (Blueprint $table) {
            $table->unsignedInteger('present_days')->default(0)->after('overtime_rate');
            $table->unsignedInteger('absent_days')->default(0)->after('present_days');
            $table->unsignedInteger('late_days')->default(0)->after('absent_days');
            $table->unsignedInteger('half_days')->default(0)->after('late_days');
            $table->unsignedInteger('unpaid_leave_days')->default(0)->after('half_days');
            $table->decimal('overtime_pay', 12, 2)->default(0.00)->after('unpaid_leave_days');
            $table->decimal('late_deduction', 12, 2)->default(0.00)->after('overtime_pay');
            $table->decimal('absence_deduction', 12, 2)->default(0.00)->after('late_deduction');
            $table->decimal('half_day_deduction', 12, 2)->default(0.00)->after('absence_deduction');
            $table->decimal('unpaid_leave_deduction', 12, 2)->default(0.00)->after('half_day_deduction');
            $table->decimal('bonuses', 12, 2)->default(0.00)->after('unpaid_leave_deduction');
            $table->decimal('advances', 12, 2)->default(0.00)->after('bonuses');
            $table->decimal('other_deductions', 12, 2)->default(0.00)->after('advances');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('staff_payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'present_days', 'absent_days', 'late_days', 'half_days', 'unpaid_leave_days',
                'overtime_pay', 'late_deduction', 'absence_deduction', 'half_day_deduction',
                'unpaid_leave_deduction', 'bonuses', 'advances', 'other_deductions',
                'approved_by', 'approved_at',
            ]);
        });
    }
};
