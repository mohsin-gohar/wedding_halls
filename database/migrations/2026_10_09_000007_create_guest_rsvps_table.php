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
        Schema::create('guest_rsvps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('guest_name', 120);
            $table->string('phone', 50)->index();
            $table->unsignedInteger('attending_count')->default(1);
            $table->enum('rsvp_status', ['ATTENDING', 'DECLINED', 'TENTATIVE'])->default('ATTENDING');
            $table->text('last_reply_message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'booking_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_rsvps');
    }
};
