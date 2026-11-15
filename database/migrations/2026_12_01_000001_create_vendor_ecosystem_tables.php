<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 (Enterprise Expansion): Generic multi-vendor foundation.
 *
 * Creates dynamic vendor categories and the per-vendor business resources
 * (services, packages, portfolio, verification documents, availability)
 * shared by every vendor category — while the existing hall ERP (venues,
 * halls, bookings, kitchen, payroll) stays untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // 1. Dynamic vendor categories (Super-Admin manageable, no code change
        //    needed to introduce new service verticals in the future).
        // ------------------------------------------------------------------
        if (! Schema::hasTable('vendor_categories')) {
            Schema::create('vendor_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);                       // Wedding Halls, Photography, Catering…
                $table->string('slug', 80)->unique();             // wedding-halls, photography…
                $table->string('tagline', 160)->nullable();       // Marketing one-liner
                $table->string('icon', 60)->nullable();           // Heroicon name for UI
                $table->string('hero_color', 20)->default('#C6A15B');
                // Which booking form/flow this category uses:
                // SHIFT_VENUE | DATE_EVENT | HOURLY | APPOINTMENT | PER_HEAD | PROJECT | CONSULTATION | TRANSPORT
                $table->string('booking_mode', 30)->default('DATE_EVENT');
                $table->json('features')->nullable();             // Category module toggles
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        // ------------------------------------------------------------------
        // 2. Tenant (vendor business) marketplace extension.
        // ------------------------------------------------------------------
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'vendor_category_id')) {
                $table->foreignId('vendor_category_id')
                    ->nullable()
                    ->after('business_type')
                    ->constrained('vendor_categories')
                    ->nullOnDelete();
            }
        });

        $marketplaceColumns = [
            'about' => 'text',                                  // Business bio
            'cover_image' => 'string',
            'service_areas' => 'json',                          // Cities/areas served
            'rating_avg' => 'decimal:3,2',
            'rating_count' => 'unsignedInteger',
            'min_budget' => 'decimal:12,2',
            'max_budget' => 'decimal:12,2',
            'is_featured' => 'boolean',
            'featured_until' => 'date',
            'verified_at' => 'timestamp',
            'onboarding_completed_at' => 'timestamp',
            'onboarding_step' => 'unsignedInteger',
            'marketplace_visible' => 'boolean',                 // Live on public directory
            'profile_views' => 'unsignedInteger',
        ];

        foreach ($marketplaceColumns as $column => $type) {
            if (Schema::hasColumn('tenants', $column)) {
                continue;
            }

            Schema::table('tenants', function (Blueprint $table) use ($column, $type) {
                if ($type === 'text') {
                    $table->text($column)->nullable();
                } elseif ($type === 'json') {
                    $table->json($column)->nullable();
                } elseif (str_starts_with($type, 'decimal')) {
                    $table->decimal($column, 12, 2)->nullable();
                } elseif (str_starts_with($type, 'unsigned')) {
                    $table->unsignedInteger($column)->default(0);
                } elseif ($type === 'boolean') {
                    $table->boolean($column)->default(false);
                } else {
                    $table->{$type}($column)->nullable();
                }
            });
        }

        // ------------------------------------------------------------------
        // 3. Generic vendor service catalog (any category).
        // ------------------------------------------------------------------
        if (! Schema::hasTable('vendor_services')) {
            Schema::create('vendor_services', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('vendor_category_id')->nullable()->constrained('vendor_categories')->nullOnDelete();
                $table->string('name', 150);
                $table->text('description')->nullable();
                // FIXED | PER_HEAD | HOURLY | DAY | PROJECT | CONSULT
                $table->string('pricing_type', 20)->default('FIXED');
                $table->decimal('price_from', 12, 2)->nullable();
                $table->decimal('price_to', 12, 2)->nullable();
                $table->string('unit', 40)->nullable();          // "per event", "per hour", "per head"…
                $table->integer('duration_minutes')->nullable();
                $table->json('highlights')->nullable();          // Bullet features
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
                $table->index(['tenant_id', 'is_active']);
                $table->index('vendor_category_id');
            });
        }

        // ------------------------------------------------------------------
        // 4. Vendor packages (bundles, any category).
        // ------------------------------------------------------------------
        if (! Schema::hasTable('vendor_packages')) {
            Schema::create('vendor_packages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('vendor_category_id')->nullable()->constrained('vendor_categories')->nullOnDelete();
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->json('items')->nullable();               // What's included
                $table->string('pricing_type', 20)->default('FIXED');
                $table->decimal('price', 12, 2)->nullable();
                $table->decimal('price_from', 12, 2)->nullable();
                $table->string('unit', 40)->nullable();
                $table->string('image', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
                $table->index(['tenant_id', 'is_active']);
            });
        }

        // ------------------------------------------------------------------
        // 5. Portfolio media (images + videos).
        // ------------------------------------------------------------------
        if (! Schema::hasTable('vendor_portfolio_media')) {
            Schema::create('vendor_portfolio_media', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('type', 10)->default('IMAGE');    // IMAGE | VIDEO
                $table->string('path', 255);
                $table->string('thumbnail_path', 255)->nullable();
                $table->string('caption', 200)->nullable();
                $table->string('category', 80)->nullable();      // e.g. "Mehndi", "Baraat", "Outdoor"
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
                $table->index(['tenant_id', 'type']);
            });
        }

        // ------------------------------------------------------------------
        // 6. Verification documents (secure storage, admin-reviewed).
        // ------------------------------------------------------------------
        if (! Schema::hasTable('vendor_documents')) {
            Schema::create('vendor_documents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('doc_type', 40);                  // CNIC | NTN | LICENSE | BUSINESS_REG | OTHER
                $table->string('file_path', 255);
                $table->string('original_name', 150)->nullable();
                $table->string('status', 20)->default('PENDING'); // PENDING | APPROVED | REJECTED
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('review_notes')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }

        // ------------------------------------------------------------------
        // 7. Availability (weekly recurring + date-specific).
        // ------------------------------------------------------------------
        if (! Schema::hasTable('vendor_availability')) {
            Schema::create('vendor_availability', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->unsignedTinyInteger('day_of_week')->nullable(); // 0=Sun … 6=Sat (weekly rule)
                $table->date('date')->nullable();                        // Date-specific override
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->boolean('is_available')->default(true);
                $table->string('note', 200)->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'day_of_week']);
                $table->index(['tenant_id', 'date']);
            });
        }

        // Index for the vendor directory (category + visibility + city lookups).
        Schema::table('tenants', function (Blueprint $table) {
            if (! $this->indexExists('tenants', 'tenants_marketplace_idx')) {
                Schema::table('tenants', function (Blueprint $t) {
                    $t->index(['vendor_category_id', 'marketplace_visible', 'city'], 'tenants_marketplace_idx');
                });
            }
        });
    }

    public function down(): void
    {
        // Child tables first, then the tenant extension, then categories.
        foreach (['vendor_availability', 'vendor_documents', 'vendor_portfolio_media', 'vendor_packages', 'vendor_services'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('tenants', function (Blueprint $table) {
            foreach (['vendor_category_id', 'about', 'cover_image', 'service_areas', 'rating_avg', 'rating_count',
                'min_budget', 'max_budget', 'is_featured', 'featured_until', 'verified_at',
                'onboarding_completed_at', 'onboarding_step', 'marketplace_visible', 'profile_views'] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('vendor_categories');
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->pluck('name')->contains($index);
    }
};
