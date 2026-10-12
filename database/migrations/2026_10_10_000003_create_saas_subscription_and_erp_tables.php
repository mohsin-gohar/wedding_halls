<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Subscription Plans Table
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 12, 2)->default(0.00); // PKR
            $table->decimal('price_yearly', 12, 2)->default(0.00); // PKR
            $table->integer('max_halls')->default(1); // -1 = unlimited
            $table->integer('max_staff')->default(3); // -1 = unlimited
            $table->integer('max_monthly_bookings')->default(20); // -1 = unlimited
            $table->boolean('has_custom_domain')->default(false);
            $table->boolean('has_kitchen_kot')->default(true);
            $table->boolean('has_whatsapp_automation')->default(false);
            $table->boolean('has_accounting_reports')->default(true);
            $table->integer('trial_days')->default(14);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Enhance Tenants Table with Subscription Details
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('subscription_plan')->constrained('subscription_plans')->nullOnDelete();
            $table->string('subscription_status', 30)->default('TRIAL')->after('subscription_plan_id'); // TRIAL, ACTIVE, PAST_DUE, SUSPENDED, CANCELLED
            $table->string('billing_cycle', 20)->default('MONTHLY')->after('subscription_status'); // MONTHLY, YEARLY
            $table->timestamp('subscription_starts_at')->nullable()->after('billing_cycle');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_starts_at');
        });

        // 3. Subscription Payments Table
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans');
            $table->string('billing_cycle', 20)->default('MONTHLY');
            $table->decimal('amount', 12, 2); // PKR
            $table->string('payment_method', 40)->default('BANK_TRANSFER'); // BANK_TRANSFER, JAZZCASH, EASYPAISA, CREDIT_CARD, CASH
            $table->string('transaction_reference', 100)->nullable();
            $table->string('proof_attachment', 255)->nullable();
            $table->string('status', 30)->default('PENDING'); // PENDING, APPROVED, REJECTED
            $table->text('notes')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // 4. Expenses Table for Vendor ERP
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('hall_id')->nullable()->constrained('halls')->nullOnDelete();
            $table->string('category', 60); // GENERATOR_DIESEL, WAPDA_ELECTRICITY, RAW_FOOD_MEAT, FRESH_FLORAL_DECOR, STAFF_WAGES, MAINTENANCE_REPAIRS, CATERING_SUPPLIES, MARKETING_ADS, OTHER
            $table->string('title', 150);
            $table->decimal('amount', 12, 2); // PKR
            $table->date('expense_date');
            $table->string('payee', 150)->nullable();
            $table->string('payment_method', 40)->default('CASH');
            $table->string('receipt_number', 80)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'expense_date']);
            $table->index(['tenant_id', 'category']);
        });

        // 5. Audit Logs Table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('entity_type', 80)->nullable();
            $table->string('entity_id', 80)->nullable();
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('subscription_payments');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn([
                'subscription_plan_id',
                'subscription_status',
                'billing_cycle',
                'subscription_starts_at',
                'subscription_expires_at',
            ]);
        });

        Schema::dropIfExists('subscription_plans');
    }
};
