<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cash-only financial integrity hardening.
 *
 * - Payment references are unique PER TENANT (a voucher number may legitimately
 *   be reused by a different venue; the old global index wrongly blocked that).
 * - Payment and audit history is never cascade-deleted when a booking/tenant is
 *   removed.
 * - The queue's configured `database-uuids` failed-job table is created.
 *
 * Existing financial references are never silently rewritten. Duplicate legacy
 * references stop this migration and require a reviewed data-repair operation.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createFailedJobsTable();
        $this->scopePaymentReferencesToTenant();
        $this->protectHistoryFromCascadeDeletes();
    }

    public function down(): void
    {
        // Intentionally irreversible: re-enabling destructive cascades or
        // dropping the failed-job table would risk financial data loss.
    }

    private function createFailedJobsTable(): void
    {
        if (Schema::hasTable('failed_jobs')) {
            return;
        }

        Schema::create('failed_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    private function scopePaymentReferencesToTenant(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        // Empty references carry no idempotency value; normalize them to NULL.
        DB::table('payments')
            ->whereRaw("TRIM(COALESCE(transaction_reference, '')) = ''")
            ->update(['transaction_reference' => null]);

        $duplicates = DB::table('payments')
            ->select('tenant_id', 'payment_method', 'transaction_reference')
            ->whereNotNull('transaction_reference')
            ->groupBy('tenant_id', 'payment_method', 'transaction_reference')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot enforce tenant-scoped payment references: duplicate legacy references exist. '
                .'No reference values were rewritten. Resolve them in a reviewed maintenance operation, then rerun migrations. '
                .'Sample: '.json_encode($duplicates->toArray(), JSON_THROW_ON_ERROR)
            );
        }

        if (Schema::hasIndex('payments', 'payments_gateway_reference_unique')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->dropUnique('payments_gateway_reference_unique');
            });
        }

        if (! Schema::hasIndex('payments', 'payments_cash_reference_unique')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->unique(
                    ['tenant_id', 'payment_method', 'transaction_reference'],
                    'payments_cash_reference_unique'
                );
            });
        }
    }

    private function protectHistoryFromCascadeDeletes(): void
    {
        if (Schema::hasTable('payments')) {
            $this->restrictForeignKey('payments', 'tenant_id', 'tenants');
            $this->restrictForeignKey('payments', 'booking_id', 'bookings');
        }

        if (Schema::hasTable('audit_logs')) {
            $this->restrictForeignKey('audit_logs', 'tenant_id', 'tenants');
        }
    }

    private function restrictForeignKey(string $table, string $column, string $referenceTable): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        $constraint = "{$table}_{$column}_foreign";

        if (! $this->foreignKeyExists($table, $constraint)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referenceTable, $constraint): void {
            $blueprint->dropForeign($constraint);
            $blueprint->foreign($column)
                ->references('id')
                ->on($referenceTable)
                ->restrictOnDelete();
        });
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return collect(Schema::getForeignKeys($table))
                ->contains(fn (array $foreignKey): bool => ($foreignKey['name'] ?? null) === $constraint);
        }

        $result = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $constraint, 'FOREIGN KEY']
        );

        return count($result) > 0;
    }
};
