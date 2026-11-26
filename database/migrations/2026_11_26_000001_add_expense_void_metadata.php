<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->boolean('is_voided')->default(false)->after('notes');
            $table->timestamp('voided_at')->nullable()->after('is_voided');
            $table->foreignId('voided_by_user_id')->nullable()->after('voided_at')
                ->constrained('users')->nullOnDelete();
            $table->string('void_reason', 500)->nullable()->after('voided_by_user_id');
            $table->index(['tenant_id', 'is_voided', 'expense_date'], 'expenses_tenant_voided_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex('expenses_tenant_voided_date_idx');
            $table->dropConstrainedForeignId('voided_by_user_id');
            $table->dropColumn(['is_voided', 'voided_at', 'void_reason']);
        });
    }
};
