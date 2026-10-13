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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('category', [
                'STARTERS',
                'MAINS',
                'RICE',
                'BREADS',
                'DESSERTS',
                'BEVERAGES',
                'SALADS'
            ])->default('MAINS');
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            // KOT raw material ratio per guest (for kitchen calculations)
            $table->decimal('raw_meat_ratio_kg', 6, 4)->default(0.0000);
            $table->decimal('raw_rice_ratio_kg', 6, 4)->default(0.0000);
            $table->decimal('raw_oil_ratio_liters', 6, 4)->default(0.0000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'category']);
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 150); // e.g. Royal Barat Package, Platinum Valima Buffet
            $table->text('description')->nullable();
            $table->decimal('per_head_price', 10, 2);
            $table->json('menu_items_json')->nullable(); // JSON list of dish IDs / names
            $table->unsignedInteger('min_guests')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
        Schema::dropIfExists('menu_items');
    }
};
