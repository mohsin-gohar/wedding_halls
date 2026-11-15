<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhanced subscription plans table for Jashn Planner enterprise tiers
        if (! Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50);  // FREE, PRO, ENTERPRISE
                $table->string('slug', 50)->unique();
                $table->text('description');
                $table->decimal('price_monthly', 12, 2)->default(0.00);
                $table->decimal('price_yearly', 12, 2)->nullable();
                $table->integer('max_venues')->default(1);  // -1 = unlimited
                $table->integer('max_staff')->default(5);
                $table->integer('max_bookings')->default(100);
                $table->boolean('has_custom_domain')->default(false);
                $table->boolean('has_ai_features')->default(true);
                $table->boolean('has_whatsApp')->default(true);
                $table->boolean('has_rfq')->default(true);
                $table->boolean('has_mood_board')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
