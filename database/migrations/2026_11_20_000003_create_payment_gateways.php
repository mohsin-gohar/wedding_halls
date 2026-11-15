<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 3. Payment gateways table for multi-currency support
        if (! Schema::hasTable('payment_gateways')) {
            Schema::create('payment_gateways', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('gateway_name', 50); // STRIPE, PAYPAL, JAZZCASH, SADAPAY
                $table->string('gateway_slug', 50)->unique();
                $table->string('status', 20)->default('INACTIVE'); // INACTIVE, ACTIVE, ERROR
                $table->string('currency', 10)->default('PKR');
                $table->text('config')->nullable(); // JSON config for each gateway
                $table->timestamps();
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
