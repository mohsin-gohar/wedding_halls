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
        Schema::create('utility_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();

            // Generator Run & Fuel Metrics
            $table->decimal('generator_run_hours', 6, 2)->default(0.00);
            $table->decimal('diesel_liters_consumed', 8, 2)->default(0.00);
            $table->decimal('diesel_rate_per_liter', 8, 2)->default(280.00);

            // WAPDA / Grid Electricity Units
            $table->decimal('wapda_units_consumed', 8, 2)->default(0.00);
            $table->decimal('wapda_unit_rate', 8, 2)->default(65.00);

            // Cost Formulation
            $table->decimal('total_utility_cost', 10, 2)->default(0.00);

            $table->string('logged_by', 120)->nullable();
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
        Schema::dropIfExists('utility_logs');
    }
};
