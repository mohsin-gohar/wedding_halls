<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hall_wishlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('hall_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamps();
            $table->unique(['hall_id', 'user_id']);
            $table->unique(['hall_id', 'session_id'])->whereNull('user_id');
            $table->index(['hall_id', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hall_wishlists');
    }
};
