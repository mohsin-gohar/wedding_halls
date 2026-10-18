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
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('hall_id')->constrained('halls')->restrictOnDelete();
            $table->foreignUuid('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Client Information
            $table->string('customer_name', 120);
            $table->string('customer_phone', 50);
            $table->string('customer_cnic', 50)->nullable();
            $table->string('customer_email', 255)->nullable();

            // Event Details
            $table->string('event_type', 50)->default('Barat'); // Barat, Valima, Mehndi, Corporate, etc.
            $table->date('event_date');
            $table->enum('slot_type', ['AFTERNOON', 'EVENING', 'NIGHT', 'FULL_DAY'])->default('EVENING');
            $table->unsignedInteger('guest_count')->default(200);

            // Financial Calculations
            $table->decimal('per_head_rate', 10, 2)->default(0.00);
            $table->decimal('hall_rent', 12, 2)->default(0.00);
            $table->decimal('addons_total', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2)->default(0.00);
            $table->decimal('advance_paid', 12, 2)->default(0.00);
            $table->decimal('balance_due', 12, 2)->default(0.00);

            // Custom dynamic menu and add-ons selection
            $table->json('custom_menu_json')->nullable();
            $table->json('addons_json')->nullable();

            // State Machine
            $table->enum('status', ['INQUIRY', 'HOLD', 'CONFIRMED', 'COMPLETED', 'CANCELLED'])->default('CONFIRMED');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Composite Index for Slot Overlap Queries (Concurrency safely enforced by BookingService mutex & row locks)
            $table->index(['hall_id', 'event_date', 'slot_type'], 'idx_hall_event_slot');
            $table->index(['tenant_id', 'event_date']);
            $table->index(['customer_phone', 'customer_cnic']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
