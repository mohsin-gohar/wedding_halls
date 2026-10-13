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
        Schema::create('kitchen_kots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('kot_number', 50)->unique();
            $table->unsignedInteger('guest_count');

            // Automatic ingredient multiplier weights
            $table->decimal('raw_meat_kg', 8, 2)->default(0.00);      // e.g. Mutton / Chicken kg
            $table->decimal('raw_rice_kg', 8, 2)->default(0.00);      // e.g. Basmati Rice kg
            $table->decimal('raw_oil_liters', 8, 2)->default(0.00);   // e.g. Cooking Oil liters
            
            // JSON breakdown for detailed chef recipes, spices, yogurt, salads
            $table->json('ingredient_details')->nullable();
            $table->json('dishes_summary')->nullable();

            $table->enum('preparation_status', [
                'PENDING',
                'PREPARING',
                'READY',
                'SERVED',
                'CANCELLED'
            ])->default('PENDING');

            $table->text('custom_instructions')->nullable();
            $table->text('chef_notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'booking_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchen_kots');
    }
};
