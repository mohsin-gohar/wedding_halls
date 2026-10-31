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
        Schema::create('hall_chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hall_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ip_address', 45)->index(); // IPv4/IPv6
            $table->string('session_id', 100)->index(); // for guest tracking
            $table->string('sender_type', 20); // 'customer' | 'hall_owner'
            $table->string('message_type', 20)->default('text'); // 'text' | 'voice'
            $table->text('message'); // text content or voice file path
            $table->string('voice_file', 255)->nullable(); // path to stored voice file
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['hall_id', 'sender_type', 'created_at']);
            $table->index(['ip_address', 'session_id', 'hall_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hall_chat_messages');
    }
};
