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
        Schema::create('floor_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('hall_id')->constrained('halls')->cascadeOnDelete();
            $table->foreignUuid('booking_id')->nullable()->constrained('bookings')->cascadeOnDelete();
            $table->string('name', 120);
            $table->json('layout_data')->nullable(); // Tables, Sofas, Stage, Buffet
            $table->unsignedInteger('total_seats')->default(0);
            $table->boolean('is_template')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'hall_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('floor_plans');
    }
};
