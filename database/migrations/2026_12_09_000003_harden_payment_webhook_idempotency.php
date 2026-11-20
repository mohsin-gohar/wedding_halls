<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Signed gateway webhooks still need a durable idempotency key. Enforce the
 * gateway reference at the database level and allow Stripe as a real payment
 * method (the strict enum previously rejected it). Pre-existing duplicate
 * references are preserved but renamed deterministically before indexing.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->renameHistoricDuplicateReferences();

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
     * Keep every historical payment row, but make duplicate gateway references
     * unique so the index can be created on installations that already have
     * repeated references from older code or seeders.
     */
    private function renameHistoricDuplicateReferences(): void
    {
        // MySQL permits multiple NULLs in a unique index but treats '' as an
        // ordinary value. Normalize legacy empty strings before indexing so
        // two blank references cannot make the migration fail.
        DB::table('payments')
            ->where('transaction_reference', '')
            ->update(['transaction_reference' => null]);

        $duplicates = DB::table('payments')
            ->select('payment_method', 'transaction_reference', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('transaction_reference')
            ->groupBy('payment_method', 'transaction_reference')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $rows = DB::table('payments')
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
    }
};
