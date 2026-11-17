<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1/3 (Enterprise Expansion): Polymorphic service booking engine,
 * vendor finance (wallet ledger, payouts) and platform settings.
 *
 * `service_bookings` serves every non-hall category (photography, catering,
 * decoration, beauty, planning, transport…). Existing hall bookings on the
 * `bookings` table remain the single source of truth for venues.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // 1. Service bookings — polymorphic on the "bookable" offering.
        // ------------------------------------------------------------------
        if (! Schema::hasTable('service_bookings')) {
            Schema::create('service_bookings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('vendor_category_id')->nullable()->constrained('vendor_categories')->nullOnDelete();
                $table->nullableUuidMorphs('bookable');          // VendorService | VendorPackage | PhotographerPackage | CarFleet…
                $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('rfq_bid_id')->nullable()->constrained('rfq_bids')->nullOnDelete();
                // Column only here: customer_events is created in the NEXT migration file.
                // The foreign key is added afterwards (driver-supported backends) so the
                // migration order stays valid on MySQL/MariaDB/PostgreSQL.
                $table->uuid('customer_event_id')->nullable();

                $table->string('booking_reference', 30)->unique();
                $table->string('event_name', 150)->nullable();
                $table->date('event_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->decimal('hours', 5, 2)->nullable();      // Hourly categories
                $table->unsignedInteger('guest_count')->nullable(); // Per-head categories
                $table->text('requirements')->nullable();         // Customer brief
                $table->json('requirements_json')->nullable();

                $table->decimal('quoted_amount', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('advance_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('commission_amount', 12, 2)->default(0);
                $table->decimal('net_vendor_amount', 12, 2)->default(0);

                // DRAFT | PENDING_VENDOR | QUOTE_SENT | ACCEPTED | CONFIRMED | IN_PROGRESS
                // | COMPLETED | CANCELLED | DECLINED
                $table->string('status', 20)->default('PENDING_VENDOR')->index();
                // UNPAID | ADVANCE_PAID | PARTIAL | PAID | REFUNDED
                $table->string('payment_status', 20)->default('UNPAID')->index();

                // Photography/media deliverables tracking
                $table->json('deliverables_json')->nullable();
                $table->string('delivery_status', 20)->nullable(); // PENDING | EDITING | DELIVERED
                $table->text('cancellation_reason')->nullable();
                $table->string('cancellation_policy', 40)->nullable();

                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['customer_id', 'status']);
                $table->index(['event_date', 'status']);
            });
        }

        // ------------------------------------------------------------------
        // 2. Service payments — manual/gateway advance + installment flows.
        // ------------------------------------------------------------------
        if (! Schema::hasTable('service_payments')) {
            Schema::create('service_payments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('service_booking_id')->constrained('service_bookings')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();

                $table->decimal('amount', 12, 2);
                // JAZZCASH | EASYPAISA | BANK_TRANSFER | CARD | CASH | WALLET
                $table->string('method', 30)->default('BANK_TRANSFER');
                $table->string('gateway', 40)->nullable();
                $table->string('gateway_ref', 100)->nullable();
                $table->string('reference', 60)->nullable();     // Customer-entered transfer ref
                $table->string('proof_path', 255)->nullable();   // Receipt screenshot
                // PENDING | VERIFIED | REJECTED | REFUNDED
                $table->string('status', 20)->default('PENDING')->index();
                $table->string('kind', 20)->default('ADVANCE');  // ADVANCE | INSTALLMENT | FULL | REFUND
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['service_booking_id', 'status']);
                $table->index(['tenant_id', 'status']);
            });
        }

        // ------------------------------------------------------------------
        // 3. Payout requests (vendor withdrawal → admin approval).
        // Created before wallet_transactions because the wallet ledger
        // references vendor_payouts (MySQL rejects FKs to later tables).
        // ------------------------------------------------------------------
        if (! Schema::hasTable('vendor_payouts')) {
            Schema::create('vendor_payouts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('method', 30)->default('BANK_TRANSFER');
                $table->json('account_details')->nullable();     // Bank/IBAN/JazzCash account
                // REQUESTED | PROCESSING | PAID | REJECTED
                $table->string('status', 20)->default('REQUESTED')->index();
                $table->string('reference', 60)->nullable();
                $table->text('note')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('requested_at')->useCurrent();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
            });
        }

        // ------------------------------------------------------------------
        // 4. Wallet ledger (immutable, balance_after snapshot per row).
        // ------------------------------------------------------------------
        if (! Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('vendor_wallet_id')->nullable()->constrained('vendor_wallets')->nullOnDelete();
                $table->string('type', 10);                      // CREDIT | DEBIT
                // BOOKING_PAYMENT | COMMISSION | PAYOUT | REFUND | ADJUSTMENT | SUBSCRIPTION
                $table->string('source', 30);
                $table->decimal('amount', 12, 2);
                $table->decimal('balance_after', 12, 2);
                $table->foreignUuid('service_booking_id')->nullable()->constrained('service_bookings')->nullOnDelete();
                $table->foreignUuid('vendor_payout_id')->nullable()->constrained('vendor_payouts')->nullOnDelete();
                $table->string('reference', 100)->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'type']);
                $table->index('created_at');
            });
        }

        // ------------------------------------------------------------------
        // 5. Platform settings (key-value; commission defaults, feature flags).
        // ------------------------------------------------------------------
        if (! Schema::hasTable('platform_settings')) {
            Schema::create('platform_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 80)->unique();
                $table->json('value')->nullable();
                $table->string('type', 20)->default('scalar');   // scalar | json
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['platform_settings', 'vendor_payouts', 'wallet_transactions', 'service_payments', 'service_bookings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
