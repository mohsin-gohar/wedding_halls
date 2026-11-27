<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HISTORICAL MIGRATION — retained so existing installations keep a valid
 * migration history. This originally added gateway idempotency indexes and the
 * Stripe method label. Those online-payment paths have since been removed; the
 * later cash-only migration supersedes the unique index. Historical rows and
 * enum labels are deliberately preserved and are never rewritten or deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->assertHistoricReferencesAreUnique();

        Schema::table('payments', function (Blueprint $table): void {
            $table->enum('payment_method', [
                'CASH',
                'BANK_TRANSFER',
                'ONLINE',
                'JAZZCASH',
                'EASYPAISA',
                'CREDIT_CARD',
                'CHEQUE',
                'STRIPE',
            ])->default('CASH')->change();

            $table->unique(['payment_method', 'transaction_reference'], 'payments_gateway_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_gateway_reference_unique');

            $table->enum('payment_method', [
                'CASH',
                'BANK_TRANSFER',
                'ONLINE',
                'JAZZCASH',
                'EASYPAISA',
                'CREDIT_CARD',
                'CHEQUE',
            ])->default('CASH')->change();
        });
    }

    /**
     * Legacy duplicate references must be resolved by a reviewed data-owner
     * decision. Never rewrite a receipt's historical reference automatically.
     */
    private function assertHistoricReferencesAreUnique(): void
    {
        // Blank references are equivalent to "not supplied" and may be NULL
        // under a unique index; normalize only empty values, not real references.
        DB::table('payments')
            ->whereRaw("TRIM(COALESCE(transaction_reference, '')) = ''")
            ->update(['transaction_reference' => null]);

        $duplicates = DB::table('payments')
            ->select('payment_method', 'transaction_reference')
            ->whereNotNull('transaction_reference')
            ->groupBy('payment_method', 'transaction_reference')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot enforce payment gateway reference uniqueness: duplicate historical references exist. '
                .'No receipt references were rewritten. Resolve duplicates in a reviewed maintenance operation, then rerun migrations. '
                .'Sample: '.json_encode($duplicates->toArray(), JSON_THROW_ON_ERROR)
            );
        }
    }
};
