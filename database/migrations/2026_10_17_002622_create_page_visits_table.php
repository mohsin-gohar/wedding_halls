<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('path', 500);
            $table->string('route_name', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('visited_at')->useCurrent();
            $table->timestamps();

            $table->index(['path', 'visited_at']);
            $table->index(['route_name', 'visited_at']);
            $table->index(['visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
