<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 9. Menu items - catering menu builder
        if (! Schema::hasTable('menu_items')) {
            Schema::create('menu_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('caterer_id')->constrained('users')->cascadeOnDelete(); // Caterer as user
                $table->string('name', 100);  // e.g., "Chicken Biryani"
                $table->enum('category', ['STARTER', 'MAIN', 'DESSERT', 'BEVERAGE']);
                $table->enum('cuisine', ['MUGHAL', 'CONTINENTAL', 'DESI', 'ITALIAN', 'CHINESE', 'ARABIC']);
                $table->decimal('per_head_price', 10, 2);
                $table->integer('min_guest_count')->default(50);
                $table->boolean('is_active')->default(true);
                $table->text('ingredients')->nullable();
                $table->json('allergens')->nullable();
                $table->timestamps();
            });
        }

        // 10. Menu combinations - for live total calculations
        if (! Schema::hasTable('menu_combinations')) {
            Schema::create('menu_combinations', function (Blueprint $table) {
                $table->id();
                $table->uuid('event_booking_id');
                $table->decimal('subtotal', 12, 2);
                $table->decimal('tax_amount', 12, 2);
                $table->decimal('total', 12, 2);
                $table->timestamps();
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('menu_combinations');
        Schema::dropIfExists('menu_items');
    }
};
