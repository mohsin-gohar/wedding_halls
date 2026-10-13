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
        // 1. Add weekly price to subscription plans if missing
        if (Schema::hasTable('subscription_plans')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('subscription_plans', 'price_weekly')) {
                    $table->decimal('price_weekly', 12, 2)->default(5000.00)->after('description');
                }
            });
        }

        // 2. Add website customization settings to tenants and halls
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                if (!Schema::hasColumn('tenants', 'website_settings')) {
                    $table->json('website_settings')->nullable()->after('verification_notes');
                }
            });
        }

        if (Schema::hasTable('halls')) {
            Schema::table('halls', function (Blueprint $table) {
                if (!Schema::hasColumn('halls', 'website_settings')) {
                    $table->json('website_settings')->nullable()->after('policies');
                }
            });
        }

        // 3. Staff Attendance
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 30)->default('PRESENT'); // PRESENT, ABSENT, HALF_DAY, ON_LEAVE, LATE
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->decimal('overtime_hours', 5, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'date']);
            $table->index(['tenant_id', 'date']);
        });

        // 4. Staff Leaves
        Schema::create('staff_leaves', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('leave_type', 40)->default('CASUAL'); // CASUAL, SICK, ANNUAL, EMERGENCY
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days')->default(1);
            $table->text('reason');
            $table->string('status', 30)->default('PENDING'); // PENDING, APPROVED, REJECTED
            $table->foreignId('action_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // 5. Staff Payroll & Salary Slips
        Schema::create('staff_payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('month_year', 10); // e.g., '2026-10'
            $table->decimal('basic_salary', 12, 2)->default(35000.00);
            $table->decimal('overtime_hours', 6, 2)->default(0.00);
            $table->decimal('overtime_rate', 10, 2)->default(250.00);
            $table->decimal('allowances', 12, 2)->default(0.00);
            $table->decimal('deductions', 12, 2)->default(0.00);
            $table->decimal('net_salary', 12, 2)->default(35000.00);
            $table->string('status', 30)->default('DRAFT'); // DRAFT, APPROVED, PAID
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 40)->default('CASH'); // BANK_TRANSFER, CASH, CHEQUE
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'month_year']);
            $table->index(['tenant_id', 'month_year']);
        });

        // 6. Inventory Items
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('category', 60)->default('RAW_FOOD'); // RAW_FOOD, BEVERAGES, CROCKERY, LINEN, FUEL, CLEANING, OTHER
            $table->string('unit', 30)->default('KG'); // KG, LITRE, PIECES, BAGS, CYLINDERS
            $table->decimal('current_stock', 10, 2)->default(0.00);
            $table->decimal('min_threshold', 10, 2)->default(10.00);
            $table->decimal('unit_cost', 12, 2)->default(0.00); // PKR
            $table->string('supplier_name', 120)->nullable();
            $table->string('supplier_phone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'category']);
        });

        // 7. Inventory Stock Movement Logs
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('type', 30); // STOCK_IN, STOCK_OUT, ADJUSTMENT, WASTAGE
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->string('reference_note', 255)->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });

        // 8. Purchase Orders / Supplier Procurements
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('po_number', 50);
            $table->string('supplier_name', 120);
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->string('status', 30)->default('ORDERED'); // ORDERED, RECEIVED, PAID, CANCELLED
            $table->date('order_date');
            $table->json('items_json')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('staff_payrolls');
        Schema::dropIfExists('staff_leaves');
        Schema::dropIfExists('staff_attendances');

        if (Schema::hasColumn('halls', 'website_settings')) {
            Schema::table('halls', function (Blueprint $table) {
                $table->dropColumn('website_settings');
            });
        }

        if (Schema::hasColumn('tenants', 'website_settings')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('website_settings');
            });
        }

        if (Schema::hasColumn('subscription_plans', 'price_weekly')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropColumn('price_weekly');
            });
        }
    }
};
