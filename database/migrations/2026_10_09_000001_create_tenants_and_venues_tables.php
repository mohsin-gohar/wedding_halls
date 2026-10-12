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
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('subdomain', 100)->nullable()->unique();
            $table->string('email', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('logo')->nullable();
            $table->string('subscription_plan', 50)->default('ENTERPRISE'); // STARTER, PROFESSIONAL, ENTERPRISE
            $table->string('currency', 10)->default('PKR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug', 150);
            $table->string('city', 100)->default('Lahore');
            $table->string('area', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug'], 'uq_tenant_venue_slug');
        });

        Schema::create('halls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('venue_id')->constrained('venues')->cascadeOnDelete();
            $table->string('name', 120); // e.g. Grand Ballroom, Executive Lawn, Crystal Hall
            $table->unsignedInteger('min_capacity')->default(100);
            $table->unsignedInteger('max_capacity')->default(1000);
            $table->decimal('base_rent', 12, 2)->default(100000.00);
            $table->text('description')->nullable();
            $table->boolean('has_air_conditioning')->default(true);
            $table->boolean('has_generator_backup')->default(true);
            $table->boolean('has_bridal_room')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'venue_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::dropIfExists('halls');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('tenants');
    }
};
