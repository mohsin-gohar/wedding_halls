<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Append-only corrections: original expenses are never rewritten. */
    public function up(): void
    {
        if (! Schema::hasTable('expenses')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table): void {
            if (! Schema::hasColumn('expenses', 'corrects_expense_id')) {
                $table->foreignUuid('corrects_expense_id')->nullable()->after('payroll_id')
                    ->constrained('expenses')->nullOnDelete();
            }
            if (! Schema::hasColumn('expenses', 'correction_sequence')) {
                $table->unsignedInteger('correction_sequence')->nullable()->after('corrects_expense_id');
            }
            if (! Schema::hasIndex('expenses', 'expenses_correction_unique')) {
                $table->unique(['corrects_expense_id', 'correction_sequence'], 'expenses_correction_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expenses')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table): void {
            if (Schema::hasIndex('expenses', 'expenses_correction_unique')) {
                $table->dropIndex('expenses_correction_unique');
            }
            if (Schema::hasColumn('expenses', 'correction_sequence')) {
                $table->dropColumn('correction_sequence');
            }
            if (Schema::hasColumn('expenses', 'corrects_expense_id')) {
                $table->dropConstrainedForeignId('corrects_expense_id');
            }
        });
    }
};
