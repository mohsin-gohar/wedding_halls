<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotency ledger for inbound provider webhooks. Existing financial,
     * booking and RSVP rows are never rewritten by this migration.
     */
    public function up(): void
    {
        Schema::create('processed_webhook_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider', 40);
            $table->string('message_id', 191);
            $table->string('payload_hash', 64);
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->unique(['provider', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_webhook_messages');
    }
};
