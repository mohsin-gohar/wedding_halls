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
        // 1. Enhance Tenants table for SaaS verification & onboarding
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('verification_status', 30)->default('APPROVED')->after('subscription_plan'); // PENDING, APPROVED, REJECTED, SUSPENDED
            $table->string('business_type', 80)->nullable()->after('verification_status');
            $table->string('city', 80)->nullable()->after('address');
            $table->string('province', 80)->default('Punjab')->after('city');
            $table->string('ntn_number', 50)->nullable()->after('province');
            $table->string('owner_cnic', 50)->nullable()->after('ntn_number');
            $table->text('verification_notes')->nullable()->after('owner_cnic');
        });

        // 2. Enhance Bookings table for Customer Portal & CRM Inquiry Pipeline
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_reference', 40)->nullable()->after('created_by_user_id');
            $table->foreignId('customer_id')->nullable()->after('booking_reference')->constrained('users')->nullOnDelete();
            $table->string('pipeline_stage', 40)->default('CONFIRMED')->after('status');
            // NEW_INQUIRY, CONTACTED, SITE_VISIT, QUOTATION_SENT, NEGOTIATION, DEPOSIT_PENDING, CONFIRMED, COMPLETED, CANCELLED

            // Drop rigid unique constraint so cancelled bookings do not block slots
            // (Concurrency safety is enforced atomically in BookingService via Redis/DB lockForUpdate on active bookings)
            try {
                $table->dropUnique('uq_hall_event_slot');
            } catch (Throwable $e) {
                // If index name differs or not supported by driver, ignore
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['hall_id', 'event_date', 'slot_type', 'status'], 'idx_hall_slot_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_hall_slot_status');
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['booking_reference', 'customer_id', 'pipeline_stage']);
            $table->unique(['hall_id', 'event_date', 'slot_type'], 'uq_hall_event_slot');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'verification_status',
                'business_type',
                'city',
                'province',
                'ntn_number',
                'owner_cnic',
                'verification_notes',
            ]);
        });
    }
};
