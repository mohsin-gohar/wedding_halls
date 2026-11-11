<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        // 7. Carts - unified multi-vendor cart
        if (! Schema::hasTable('carts')) {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id');
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->string('currency')->default('PKR');
            $table->string('status')->default('PENDING'); // PENDING, CONFIRMED, PAID, FAILED, COMPLETED
            $table->timestamps();
        });        }


        // 8. Cart items - support morphable bookable types
        if (! Schema::hasTable('cart_items')) {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->morphs('bookable'); // bookable_id, bookable_type
            $table->integer('quantity')->default(1);
            $table->decimal('vendor_share', 12, 2)->default(0.00); // Auto-calculated commission
            $table->decimal('vendor_commission', 12, 2)->default(0.00);
            $table->timestamps();
        });        }

    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};