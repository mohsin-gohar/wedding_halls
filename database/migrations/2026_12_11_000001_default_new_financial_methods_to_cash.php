<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make the database default for every new financial record CASH.
 *
 * This changes column defaults only. It deliberately does NOT update, rename
 * or delete any existing row: historical BANK_TRANSFER/online labels stay
 * intact for audit and reporting.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'service_payments' => 'method',
        'vendor_payouts' => 'method',
        'subscription_payments' => 'payment_method',
    ];

    public function up(): void
    {
        $this->setDefaults('CASH');
    }

    public function down(): void
    {
        $this->setDefaults('BANK_TRANSFER');
    }

    private function setDefaults(string $method): void
    {
        foreach (self::COLUMNS as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($column, $method): void {
                $blueprint->string($column, 40)->default($method)->change();
            });
        }
    }
};
