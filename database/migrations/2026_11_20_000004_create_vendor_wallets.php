<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 4. Vendor wallets table for split-payment distribution
        Schema::create('vendor_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('wallet_provider', 50); // STRIPE, PAYPAL, JAZZCASH, SADAPAY
            $table->string('account_id', 100);
            $table->string('account_name', 150);
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_wallets');
    }
};
