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
 * All changes are additive/protective and preserve every existing row.
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

        // Normalize blank references so MySQL's unique index treats them alike.
        DB::table('payments')->where('transaction_reference', '')->update(['transaction_reference' => null]);

        // Preserve history: rename duplicate (tenant, method, reference) rows
        // deterministically instead of deleting them.
        $duplicates = DB::table('payments')
            ->select('tenant_id', 'payment_method', 'transaction_reference', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('transaction_reference')
            ->groupBy('tenant_id', 'payment_method', 'transaction_reference')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $rows = DB::table('payments')
                ->where('tenant_id', $duplicate->tenant_id)
                ->where('payment_method', $duplicate->payment_method)
                ->where('transaction_reference', $duplicate->transaction_reference)
                ->where('id', '!=', $duplicate->keep_id)
                ->get(['id']);

            foreach ($rows as $row) {
                $suffix = '-LEGACY-'.$row->id;
                $reference = substr((string) $duplicate->transaction_reference, 0, 100 - strlen($suffix)).$suffix;

                DB::table('payments')->where('id', $row->id)->update(['transaction_reference' => $reference]);
            }
        }

        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasIndex('payments', 'payments_gateway_reference_unique')) {
                $table->dropUnique('payments_gateway_reference_unique');
            }

            if (! Schema::hasIndex('payments', 'payments_cash_reference_unique')) {
                $table->unique(
                    ['tenant_id', 'payment_method', 'transaction_reference'],
                    'payments_cash_reference_unique'
                );
            }
        });
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
        $constraint = "{$table}_{$column}_foreign";

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referenceTable, $constraint): void {
            $blueprint->dropForeign($constraint);
            $blueprint->foreign($column)
                ->references('id')
                ->on($referenceTable)
                ->restrictOnDelete();
        });
    }
};
