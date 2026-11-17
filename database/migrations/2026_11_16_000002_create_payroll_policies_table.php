<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->decimal('overtime_rate_per_hour', 10, 2)->default(250.00);
            $table->decimal('late_deduction_per_day', 10, 2)->default(500.00);
            $table->decimal('absence_deduction_per_day', 10, 2)->default(1000.00);
            $table->decimal('half_day_deduction', 10, 2)->default(500.00);
            $table->decimal('unpaid_leave_deduction_per_day', 10, 2)->default(1000.00);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_policies');
    }
};
