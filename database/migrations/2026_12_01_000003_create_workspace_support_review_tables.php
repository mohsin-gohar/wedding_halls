<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3/4 (Enterprise Expansion): Customer event workspace, RFQ broadcast
 * targets, reviews polymorphism and support ticketing.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // 1. Customer event workspace (Mehndi / Baraat / Walima / Corporate…).
        // ------------------------------------------------------------------
        if (! Schema::hasTable('customer_events')) {
            Schema::create('customer_events', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 150);
                $table->string('event_type', 40)->default('WEDDING'); // MEHNDI|BARAAT|WALIMA|ENGAGEMENT|BIRTHDAY|CORPORATE|WEDDING|OTHER
                $table->date('event_date')->nullable();
                $table->string('city', 80)->nullable();
                $table->string('venue_text', 200)->nullable();
                $table->unsignedInteger('guest_count')->nullable();
                $table->decimal('estimated_budget', 12, 2)->nullable();
                $table->text('notes')->nullable();
                // PLANNING | BOOKED | COMPLETED | CANCELLED
                $table->string('status', 20)->default('PLANNING')->index();
                $table->timestamps();
                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('customer_event_vendors')) {
            Schema::create('customer_event_vendors', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('customer_event_id')->constrained('customer_events')->cascadeOnDelete();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('service_booking_id')->nullable()->constrained('service_bookings')->nullOnDelete();
                // SHORTLISTED | REQUESTED | BOOKED | DROPPED
                $table->string('status', 20)->default('SHORTLISTED')->index();
                $table->text('note')->nullable();
                $table->timestamps();
                $table->unique(['customer_event_id', 'tenant_id']);
            });
        }

        if (! Schema::hasTable('customer_event_tasks')) {
            Schema::create('customer_event_tasks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('customer_event_id')->constrained('customer_events')->cascadeOnDelete();
                $table->string('title', 200);
                $table->date('due_date')->nullable();
                $table->string('status', 20)->default('PENDING'); // PENDING | IN_PROGRESS | DONE
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
                $table->index(['customer_event_id', 'status']);
            });
        }

        // ------------------------------------------------------------------
        // 2. RFQ broadcast — target specific vendors of a category.
        // ------------------------------------------------------------------
        Schema::table('rfqs', function (Blueprint $table) {
            if (! Schema::hasColumn('rfqs', 'vendor_category_id')) {
                $table->foreignId('vendor_category_id')->nullable()->after('client_id')
                    ->constrained('vendor_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('rfqs', 'title')) {
                $table->string('title', 150)->nullable()->after('vendor_category_id');
            }
            if (! Schema::hasColumn('rfqs', 'event_date')) {
                $table->date('event_date')->nullable()->after('title');
            }
            if (! Schema::hasColumn('rfqs', 'city')) {
                $table->string('city', 80)->nullable()->after('event_date');
            }
            if (! Schema::hasColumn('rfqs', 'guest_count')) {
                $table->unsignedInteger('guest_count')->nullable()->after('city');
            }
        });

        if (! Schema::hasTable('rfq_targets')) {
            Schema::create('rfq_targets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->timestamp('viewed_at')->nullable();
                $table->timestamps();
                $table->unique(['rfq_id', 'tenant_id']);
            });
        }

        // ------------------------------------------------------------------
        // 3. Reviews — make polymorphic (halls today, any vendor tomorrow).
        // ------------------------------------------------------------------
        Schema::table('reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('reviews', 'reviewable_type')) {
                $table->string('reviewable_type', 80)->nullable()->after('hall_id');
                $table->uuid('reviewable_id')->nullable()->after('reviewable_type');
                $table->index(['reviewable_type', 'reviewable_id']);
            }
            if (! Schema::hasColumn('reviews', 'is_verified_booking')) {
                $table->boolean('is_verified_booking')->default(false)->after('status');
            }
            if (! Schema::hasColumn('reviews', 'vendor_reply')) {
                $table->text('vendor_reply')->nullable()->after('is_verified_booking');
            }
        });

        // ------------------------------------------------------------------
        // 4. Support tickets.
        // ------------------------------------------------------------------
        if (! Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('ticket_no', 30)->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
                $table->string('subject', 200);
                // BOOKING | PAYMENT | DISPUTE | VERIFICATION | GENERAL
                $table->string('category', 30)->default('GENERAL');
                // LOW | MEDIUM | HIGH | URGENT
                $table->string('priority', 20)->default('MEDIUM');
                // OPEN | IN_PROGRESS | RESOLVED | CLOSED
                $table->string('status', 20)->default('OPEN')->index();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'priority']);
            });
        }

        if (! Schema::hasTable('ticket_messages')) {
            Schema::create('ticket_messages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('message');
                $table->string('attachment_path', 255)->nullable();
                $table->boolean('is_staff_reply')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index('support_ticket_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['ticket_messages', 'support_tickets'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('reviews', function (Blueprint $table) {
            // Drop the polymorphic index first (SQLite cannot drop an indexed column).
            $indexes = collect(Schema::getIndexes('reviews'))->pluck('name');
            if ($indexes->contains('reviews_reviewable_type_reviewable_id_index')) {
                $table->dropIndex('reviews_reviewable_type_reviewable_id_index');
            } elseif ($indexes->contains('reviews_reviewable_index')) {
                $table->dropIndex('reviews_reviewable_index');
            }

            foreach (['vendor_reply', 'is_verified_booking', 'reviewable_type', 'reviewable_id'] as $column) {
                if (Schema::hasColumn('reviews', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('rfq_targets');

        Schema::table('rfqs', function (Blueprint $table) {
            foreach (['guest_count', 'city', 'event_date', 'title', 'vendor_category_id'] as $column) {
                if (Schema::hasColumn('rfqs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        foreach (['customer_event_tasks', 'customer_event_vendors', 'customer_events'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
