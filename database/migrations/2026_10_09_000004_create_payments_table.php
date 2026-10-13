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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_type', [
                'TOKEN_ADVANCE',
                'SECOND_INSTALLMENT',
                'FINAL_SETTLEMENT',
                'SECURITY_DEPOSIT',
                'REFUND'
            ])->default('TOKEN_ADVANCE');
            $table->enum('payment_method', [
                'CASH',
                'BANK_TRANSFER',
                'ONLINE',
                'JAZZCASH',
                'EASYPAISA',
                'CREDIT_CARD',
                'CHEQUE'
            ])->default('CASH');
            $table->string('receipt_no', 60)->unique();
            $table->string('transaction_reference', 100)->nullable();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'booking_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
