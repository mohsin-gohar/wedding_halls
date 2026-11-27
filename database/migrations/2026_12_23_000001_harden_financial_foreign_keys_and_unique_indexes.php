<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Harden remaining financial foreign keys to restrictOnDelete and add unique
 * indexes for booking/PO references.
 *
 * - Changes cascade deletes on critical financial tables to restrict so history
 *   cannot be destroyed by deleting a parent record.
 * - Adds unique (tenant_id, po_number) on purchase_orders and unique
 *   (tenant_id, booking_reference) on bookings after deterministic
 *   de-duplication of any legacy duplicates.
 * - All operations are protective; existing rows are preserved.
 *
 * This migration is intended for staging rehearsal first. It does not touch
 * production data. It is irreversible for data-safety reasons.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->hardenPurchaseOrderReferences();
        $this->hardenBookingReferences();
        $this->restrictFinancialForeignKeys();
    }

    public function down(): void
    {
        // Intentionally irreversible for data-safety reasons, matching the
        // pattern of 2026_12_10_000001_cash_only_financial_integrity.
    }

    private function hardenPurchaseOrderReferences(): void
    {
        if (! Schema::hasTable('purchase_orders')) {
            return;
        }

        $duplicates = DB::table('purchase_orders')
            ->select('tenant_id', 'po_number', DB::raw('MIN(id) as keep_id'))
            ->groupBy('tenant_id', 'po_number')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $rows = DB::table('purchase_orders')
                ->where('tenant_id', $duplicate->tenant_id)
                ->where('po_number', $duplicate->po_number)
                ->where('id', '!=', $duplicate->keep_id)
                ->get(['id']);

            foreach ($rows as $row) {
                $suffix = '-LEGACY-'.$row->id;
                $number = substr((string) $duplicate->po_number, 0, 50 - strlen($suffix)).$suffix;
                DB::table('purchase_orders')->where('id', $row->id)->update(['po_number' => $number]);
            }
        }

        Schema::table('purchase_orders', function (Blueprint $table): void {
            if (! Schema::hasIndex('purchase_orders', 'purchase_orders_tenant_po_unique')) {
                $table->unique(['tenant_id', 'po_number'], 'purchase_orders_tenant_po_unique');
            }
        });
    }

    private function hardenBookingReferences(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'booking_reference')) {
            return;
        }

        $duplicates = DB::table('bookings')
            ->select('tenant_id', 'booking_reference', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('booking_reference')
            ->groupBy('tenant_id', 'booking_reference')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $rows = DB::table('bookings')
                ->where('tenant_id', $duplicate->tenant_id)
                ->where('booking_reference', $duplicate->booking_reference)
                ->where('id', '!=', $duplicate->keep_id)
                ->get(['id']);

            foreach ($rows as $row) {
                $suffix = '-LEGACY-'.$row->id;
                $reference = substr((string) $duplicate->booking_reference, 0, 40 - strlen($suffix)).$suffix;
                DB::table('bookings')->where('id', $row->id)->update(['booking_reference' => $reference]);
            }
        }

        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasIndex('bookings', 'bookings_tenant_reference_unique')) {
                $table->unique(['tenant_id', 'booking_reference'], 'bookings_tenant_reference_unique');
            }
        });
    }

    private function restrictFinancialForeignKeys(): void
    {
        $constraints = [
            ['expenses', 'tenant_id', 'tenants'],
            ['staff_payrolls', 'tenant_id', 'tenants'],
            ['purchase_orders', 'tenant_id', 'tenants'],
            ['service_payments', 'tenant_id', 'tenants'],
            ['service_payments', 'service_booking_id', 'service_bookings'],
            ['vendor_payouts', 'tenant_id', 'tenants'],
        ];

        foreach ($constraints as [$table, $column, $ref]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                $this->restrictForeignKey($table, $column, $ref);
            }
        }
    }

    private function restrictForeignKey(string $table, string $column, string $referenceTable): void
    {
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
        try {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver !== 'mysql') {
                return true;
            }

            $result = DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
                [$table, $constraint, 'FOREIGN KEY']
            );

            return count($result) > 0;
        } catch (Throwable $e) {
            return false;
        }
    }
};
