<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'payroll_id')) {
                $table->foreignUuid('payroll_id')->nullable()->unique()->after('hall_id')->constrained('staff_payrolls')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'payroll_id')) {
                $table->dropConstrainedForeignId('payroll_id');
            }
        });
    }
};
