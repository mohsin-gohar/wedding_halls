<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 11. Photographer packages
        Schema::create('photographer_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);  // BASIC, GOLD, PLATINUM
            $table->string('slug', 50)->unique();
            $table->decimal('hourly_rate', 8, 2);
            $table->decimal('full_day_rate', 10, 2);
            $table->boolean('includes_drone')->default(false);
            $table->boolean('includes_sameday_edit')->default(false);
            $table->text('addons')->default('[]');  // JSON array of add-ons
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 12. Car fleets management
        Schema::create('car_fleets', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('model', 100);  // e.g., "Mercedes S550"
            $table->string('color', 50);
            $table->decimal('hourly_rate', 8, 2);
            $table->integer('capacity')->default(4);
            $table->string('chauffeur_name', 100)->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_fleets');
        Schema::dropIfExists('photographer_packages');
    }
};
