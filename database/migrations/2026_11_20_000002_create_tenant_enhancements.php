<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 2. Tenant enhancements - multi-currency & payment settings
        // Guarded per-column: some subscription columns are already added by
        // 2026_10_10_000003_create_saas_subscription_and_erp_tables.php
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'subscription_plan_id')) {
                $table->foreignId('subscription_plan_id')->nullable()
                    ->constrained('subscription_plans')->nullOnDelete();
            }
            if (! Schema::hasColumn('tenants', 'subscription_status')) {
                $table->string('subscription_status', 30)->default('TRIAL'); // TRIAL, ACTIVE, PAST_DUE, SUSPENDED, CANCELLED
            }
            if (! Schema::hasColumn('tenants', 'billing_cycle')) {
                $table->string('billing_cycle', 20)->default('MONTHLY'); // MONTHLY, YEARLY
            }
            if (! Schema::hasColumn('tenants', 'subscription_starts_at')) {
                $table->timestamp('subscription_starts_at')->nullable();
            }
            if (! Schema::hasColumn('tenants', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable();
            }
            if (! Schema::hasColumn('tenants', 'currency')) {
                $table->string('currency', 10)->default('PKR'); // USD, AED, GBP, PKR
            }
            if (! Schema::hasColumn('tenants', 'commission_rate')) {
                $table->decimal('commission_rate', 5, 2)->default(20.00); // FREE: 20%, PRO: 15%, ENTERPRISE: 10%
            }
            if (! Schema::hasColumn('tenants', 'max_vendors')) {
                $table->integer('max_vendors')->default(10);
            }
            if (! Schema::hasColumn('tenants', 'max_carts')) {
                $table->integer('max_carts')->default(50);
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            foreach ([
                'subscription_plan_id',
                'subscription_status',
                'billing_cycle',
                'subscription_starts_at',
                'subscription_expires_at',
                'currency',
                'commission_rate',
                'max_vendors',
                'max_carts',
            ] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
