<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 (Enterprise Operations):
 *
 *  1. `vendor_locations` — multi-location businesses. One tenant can own
 *     several branches/studios/staff kitchens; each location carries its own
 *     address, service area and contact details.
 *  2. `photographer_packages.tenant_id` — the model already declared a
 *     `tenant()` relation against a column that never existed.
 *  3. `reviews.hall_id` nullable — reviews became polymorphic
 *     (reviewable_type / reviewable_id) but the legacy NOT NULL constraint
 *     still blocked vendor-only reviews.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // 1. Multi-location vendor businesses
        // ------------------------------------------------------------------
        if (! Schema::hasTable('vendor_locations')) {
            Schema::create('vendor_locations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name', 150);
                $table->string('slug', 150);
                $table->string('branch_type', 40)->default('BRANCH');   // HEAD_OFFICE | BRANCH | STUDIO | MOBILE
                $table->text('address')->nullable();
                $table->string('area', 120)->nullable();
                $table->string('city', 80)->nullable();
                $table->string('province', 80)->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email', 150)->nullable();
                $table->json('service_areas')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'slug']);
                $table->index(['tenant_id', 'is_active']);
            });
        }

        // ------------------------------------------------------------------
        // 2. Photographer packages become tenant-aware
        // ------------------------------------------------------------------
        if (Schema::hasTable('photographer_packages') && ! Schema::hasColumn('photographer_packages', 'tenant_id')) {
            Schema::table('photographer_packages', function (Blueprint $table) {
                $table->foreignUuid('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            });
        }

        // ------------------------------------------------------------------
        // 3. Reviews are polymorphic — the hall link must be optional
        // ------------------------------------------------------------------
        if (Schema::hasTable('reviews') && Schema::hasColumn('reviews', 'hall_id')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->uuid('hall_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_locations');

        if (Schema::hasTable('photographer_packages') && Schema::hasColumn('photographer_packages', 'tenant_id')) {
            Schema::table('photographer_packages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};
