<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utility_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('utility_logs', 'expense_id')) {
                $table->foreignUuid('expense_id')->nullable()->after('booking_id')
                    ->constrained('expenses')->nullOnDelete();
            }
            if (! Schema::hasIndex('utility_logs', 'utility_logs_expense_unique')) {
                $table->unique('expense_id', 'utility_logs_expense_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('utility_logs', function (Blueprint $table): void {
            $table->dropUnique('utility_logs_expense_unique');
            $table->dropForeign(['expense_id']);
            $table->dropColumn('expense_id');
        });
    }
};
