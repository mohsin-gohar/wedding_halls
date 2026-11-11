<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 5. Shift definitions (Lunch, Dinner, Late Night, Full Day)
        Schema::create('shift_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // LUNCH, DINNER, LATE_NIGHT, FULL_DAY
            $table->string('slug', 50)->unique();
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('multiplier', 5, 2)->default(1.00); // Price multiplier for that shift
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Venue shifts - the actual bookable shifts
        Schema::create('venue_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('venue_id')->constrained('venues')->cascadeOnDelete();
            $table->date('event_date');
            $table->foreignId('shift_definition_id')->constrained('shift_definitions');
            $table->integer('min_capacity')->default(50);
            $table->integer('max_capacity')->default(2000);
            $table->decimal('base_rent', 12, 2);
            $table->decimal('commission_rate', 5, 2)->default(20.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['venue_id', 'event_date', 'shift_definition_id'], 'uq_venue_shift_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_shifts');
        Schema::dropIfExists('shift_definitions');
    }
};
