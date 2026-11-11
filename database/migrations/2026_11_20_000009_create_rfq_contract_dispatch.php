<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        // 13. RFQ (Request for Quotation) table
        if (! Schema::hasTable('rfqs')) {
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->text('budget_range');  // e.g., "500,000 - 1,000,000 PKR"
            $table->text('requirements');  // Detailed needs description
            $table->enum('status', ['OPEN', 'CLOSED', 'AWARDED', 'CANCELLED'])->default('OPEN');  // OPEN, CLOSED, AWARDED, CANCELLED
            $table->json('preferences')->default('[]');  // Theme, color palette, guest count, etc.
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });        }


        // 14. RFQ bids table - competitive sealed bids
        if (! Schema::hasTable('rfq_bids')) {
        Schema::create('rfq_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('bid_amount', 12, 2);
            $table->text('proposal');  // Vendor's plan details
            $table->enum('status', ['PENDING', 'ACCEPTED', 'REJECTED', 'AWARDED'])->default('PENDING'); // PENDING, ACCEPTED, REJECTED, AWARDED
            $table->timestamps();

            $table->index(['rfq_id', 'status']);
            $table->index(['vendor_id', 'rfq_id']);
        });        }


        // 15. Digital contracts table
        if (! Schema::hasTable('digital_contracts')) {
        Schema::create('digital_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('booking_id');
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('contract_number', 50)->unique();
            $table->text('terms_conditions')->nullable();
            $table->string('status')->default('DRAFT'); // DRAFT, SENT, SIGNED, EXPIRED
            $table->string('signature_type')->default('ELECTRONIC'); // ELECTRONIC, DIGITAL
            $table->string('signature_url')->nullable(); // URL to signed document
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });        }


        // 16. WhatsApp/SMS dispatch log
        if (! Schema::hasTable('dispatch_logs')) {
        Schema::create('dispatch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('recipient_type', 20); // CLIENT, VENDOR
            $table->string('recipient_id', 100); // user_id or vendor_id
            $table->string('channel', 20); // WHATSAPP, SMS, EMAIL
            $table->string('template', 50); // booking_confirmation, payment_reminder, etc.
            $table->string('status')->default('PENDING'); // PENDING, SENT, DELIVERED, FAILED
            $table->text('payload')->nullable(); // The sent message data
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'channel', 'status']);
        });        }


        // 17. Mood board AI generations
        if (! Schema::hasTable('mood_board_generations')) {
        Schema::create('mood_board_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('prompt', 500);
            $table->string('status')->default('PENDING'); // PENDING, GENERATING, COMPLETED, FAILED
            $table->string('image_url')->nullable();
            $table->json('vendor_matches')->default('[]');
            $table->json('color_scheme')->default('[]');
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });        }

    }

    public function down(): void
    {
        Schema::dropIfExists('mood_board_generations');
        Schema::dropIfExists('dispatch_logs');
        Schema::dropIfExists('digital_contracts');
        Schema::dropIfExists('rfq_bids');
        Schema::dropIfExists('rfqs');
    }
};