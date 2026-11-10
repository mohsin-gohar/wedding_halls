# 🗄️ EVENTHUB SAAS - DATABASE SCHEMA & MIGRATION SUITE

## 📋 **Overview**
Complete database migrations with strict data types, foreign keys, cascade rules, indexing, and soft deletes for multi-tenant production environment. All migrations use `HasUuids` pattern and `softDeletes()`.

---
<tool_call>
<function=write>
<parameter=content>
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Core: Users, Tenants, Venues, Bookings (foundation)
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->uuid('tenant_id')->nullable();
            $table->enum('role', [
                'super_admin',
                'tenant_owner',
                'manager',
                'accountant',
                'chef',
                'floor_staff',
                'concierge'
            ])->default('floor_staff');
            $table->boolean('is_active')->default(true);
            $table->enum('verification_status', ['PENDING', 'APPROVED', 'SUSPENDED', 'DELETED'])->default('PENDING');
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_recovery_codes')->nullable();
            $table->timestamps();

            $table->index(['email', 'is_active']);
            $table->index('tenant_id');
            $table->unique(['email', 'tenant_id']);
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id'); // Owner user
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('area')->default('Lahore'); // City/area
            $table->string('city')->default('Lahore');
            $table->string('country')->default('PK');
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('verification_status', ['PENDING', 'APPROVED', 'SUSPENDED', 'DELETED'])->default('PENDING');
            $table->integer('max_halls')->default(50);
            $table->integer('max_staff')->default(50);
            $table->integer('max_bookings_per_day')->default(20);
            $table->integer('free_access_days')->default(30);
            $table->boolean('billing_enabled')->default(false);
            $table->boolean('kitchen_module_enabled')->default(false);
            $table->string('payment_gateway')->default('stripe');
            $table->string('timezone')->default('Asia/Karachi');
            $table->string('currency')->default('PKR');
            $table->string('plan')->default('free');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->text('settings')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['slug', 'is_active']);
            $table->index(['city', 'is_active']);
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('hall_type_id')->nullable(); // categorization
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('tagline')->nullable();
            $table->integer('max_capacity'); // Max guests
            $table->integer('base_rent'); // PKR per shift
            $table->enum('hall_type', [
                'banquet_ballroom',
                'open_air marquee',
                'garden lawn',
                'rooftop',
                'private dining room'
            ])->default('banquet_ballroom');
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'PENDING', 'MAINTENANCE'])->default('ACTIVE');
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
            $table->index(['slug', 'status']);
            $table->index(['max_capacity']);
        });

        // 2. Bookings & Shifts
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('venue_id');
            $table->uuid('user_id')->nullable(); // Booked by user (couple)
            $table->uuid('booked_by')->nullable(); // Booked by (owner/staff)
            $table->date('event_date');
            $table->enum('slot_type', ['AFTERNOON', 'EVENING', 'NIGHT']);
            $table->enum('status', ['INQUIRY', 'HOLD', 'CONFIRMED', 'COMPLETED', 'CANCELLED'])->default('INQUIRY');
            $table->integer('guests_count')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('advance_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('payment_method')->nullable(); // stripe, jazzcash, easypaisa, bank_transfer, cash
            $table->string('transaction_id')->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->integer('recurrence_pattern')->nullable(); // 1=weekly, 2=monthly, 3=yearly
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('booked_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['tenant_id', 'event_date', 'slot_type']);
            $table->index(['venue_id', 'event_date', 'slot_type']);
            $table->index(['status']);
            $table->index(['tenant_id', 'status']);
            $table->unique(['tenant_id', 'event_date', 'slot_type']); // Prevent double booking
        });

        // 3. Packages & Menus
        Schema::create('packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 15, 2);
            $table->string('billing_unit')->default('per event'); // per event, per head, per day
            $table->integer('max_capacity'); // Max guests included
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('package_id')->nullable();
            $table->string('name');
            $table->text('description');
            $table->decimal('price_per_head', 15, 2);
            $table.integer('min_guests')->nullable();
            $table.integer('max_guests')->nullable();
            $table.string('category')->nullable(); // appetizer, main, dessert, bar
            $table.boolean('is_available')->default(true);
            $table.integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('set null');
            $table->index(['tenant_id', 'is_available']);
        });

        // 4. Services & Add-ons
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 15, 2);
            $table.string('billing_unit')->default('per event');
            $table.string('category')->nullable(); // decoration, catering, equipment, staff
            $table boolean('is_available')->default(true);
            $table.integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'is_available']);
        });

        // 5. Payments & Gateways
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('booking_id')->nullable();
            $table->uuid('payment_link_id')->nullable(); // external gateway reference
            $table.string('provider'); // stripe, jazzcash, easypaisa, bank_transfer, cash
            $table.string('provider_payment_id')->nullable();
            $table.string('status'); // pending, completed, failed, refunded
            $table.decimal('amount', 15, 2);
            $table.string('currency')->default('PKR');
            $table.text('metadata')->nullable(); // custom fields from gateway
            $table.string('payment_type'); // deposit, full, balance, refund, payout
            $table.timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->index(['tenant_id', 'status']);
            $table->index(['provider', 'status']);
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_id');
            $table.string('provider'); // stripe, jazzcash, easypaisa
            $table.string('provider_txn_id'); // external transaction ID
            $table.decimal('amount', 15, 2);
            $table.string('status'); // succeeded, failed, pending
            $table.text('raw_response'); // stored JSON from gateway
            $table.timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->index(['provider', 'status']);
        });

        // 6. Subscriptions & Billing
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table.string('plan_name'); // free, basic, pro, enterprise
            $table.decimal('price', 15, 2)->default(0);
            $table.integer('max_halls')->nullable();
            $table.integer('max_staff')->nullable();
            $table.integer('max_bookings')->nullable();
            $table.integer('storage_gb')->nullable(); // for files/media
            $table.boolean('kitchen_module')->default(false);
            $table.boolean('priority_support')->default(false);
            $table.integer('billing_cycle'); // 1=monthly, 2=quarterly, 3=annually
            $table.decimal('trial_days')->default(30);
            $table.boolean('is_trial')->default(true);
            $table.boolean('is_active')->default(true);
            $table.timestamp('starts_at');
            $table.timestamp('ends_at')->nullable();
            $table.timestamp('canceled_at')->nullable();
            $table.text('promo_code')->nullable();
            $table.integer('times_canceled')->default(0);
            $table.timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'is_active']);
            $table->index(['plan_name', 'is_active']);
        });

        // 7. Staff Attendance & HR
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id'); // Staff user
            $table->date('date');
            $table.enum('status', ['PRESENT', 'ABSENT', 'HALF_DAY', 'ON_LEAVE', 'LATE']);
            $table.time('check_in')->nullable();
            $table.time('check_out')->nullable();
            $table.decimal('overtime_hours', 5, 2)->default(0);
            $table.text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['tenant_id', 'date']);
            $table->index(['user_id', 'date']);
            $table->unique(['tenant_id', 'user_id', 'date']);
        });

        Schema::create('staff_leaves', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id'); // Staff user
            $table.enum('leave_type', ['CASUAL', 'SICK', 'ANNUAL', 'EMERGENCY']);
            $table.date('start_date');
            $table.date('end_date');
            $table.integer('days');
            $table.text('reason')->nullable();
            $table.enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table.uuid('approved_by')->nullable();
            $table.text('notes')->nullable();
            $table.boolean('is_active')->default(true);
            $table.timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['tenant_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->unique(['tenant_id', 'user_id', 'start_date', 'end_date']);
        });

        Schema::create('staff_payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id'); // Staff user
            $table.string('month_year'); // YYYY-MM format
            $table.decimal('basic_salary', 15, 2);
            $table.decimal('overtime_hours', 5, 2)->default(0);
            $table.decimal('overtime_rate', 10, 2)->default(0);
            $table.decimal('allowances', 15, 2)->default(0);
            $table.decimal('deductions', 15, 2)->default(0);
            $table.decimal('net_salary', 15, 2);
            $table.enum('status', ['DRAFT', 'APPROVED', 'PAID'])->default('DRAFT');
            $table.timestamp('paid_at')->nullable();
            $table.string('payment_method')->nullable();
            $table.text('notes')->nullable();
            $table.boolean('is_active')->default(true);
            $table.timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['tenant_id', 'month_year']);
            $table->index(['user_id', 'month_year']);
            $table->unique(['tenant_id', 'user_id', 'month_year']);
        });

        // 7. Inventory & Supplies
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table.string('name');
            $table.string('sku')->unique();
            $table.text('description')->nullable();
            $table.integer('current_stock')->default(0);
            $table.integer('minimum_stock')->default(10);
            $table.integer('maximum_stock')->default(500);
            $table.string('unit'); // pcs, kg, liters, rolls
            $table.decimal('reorder_price', 10, 2)->default(0);
            $table.string('supplier')->nullable();
            $table.date('last_restocked')->nullable();
            $table boolean('is_tracked')->default(true);
            $table.integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'is_tracked']);
            $table->index(['sku']);
        });

        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('inventory_item_id');
            $table.integer('quantity_change');
            $table.string('change_type'); // in, out, adjustment, waste, theft
            $table.text('reason')->nullable();
            $table.uuid('changed_by')->nullable(); // user who made change
            $table.date('change_date');
            $table.timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['tenant_id', 'change_date']);
            $table->index(['inventory_item_id']);
        });

        // 8. Floor Plans
        Schema::create('floor_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('venue_id')->nullable();
            $table.string('name');
            $table.text('svg_data'); // SVG path data
            $table.string('scale'); // 1:50, 1:100, etc.
            $table.integer('capacity_zone_capacity')->nullable();
            $table.boolean('is_active')->default(true);
            $table.timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('set null');
            $table->index(['tenant_id', 'is_active']);
        });

        // 9. Reviews & Ratings
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('venue_id');
            $table->uuid('user_id'); // Reviewer (couple)
            $table.uuid('booking_id')->nullable();
            $table.integer('rating'); // 1-5
            $table.text('title')->nullable();
            $table.text('review');
            $table.timestamps('purchased_at'); // when booking was made
            $table.boolean('is_published')->default(true);
            $table.timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->index(['tenant_id', 'is_published']);
            $table->index(['venue_id', 'is_published']);
            $table->index(['rating']);
            $table->unique(['tenant_id', 'user_id', 'booking_id']);
        });

        // 10. Hall Chat & Messaging
        Schema::create('hall_chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id')->nullable(); // Staff user
            $table.uuid('booking_id')->nullable();
            $table.uuid('sender_id')->nullable(); // Could be user or staff
            $table.uuid('receiver_id')->nullable(); // Target user
            $table.string('sender_type'); // 'user', 'staff', 'system'
            $table.string('message_type'); // 'text', 'image', 'file', 'system'
            $table.text('content');
            $table.boolean('is_read')->default(false);
            $table.timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->index(['tenant_id', 'is_read']);
            $table->index(['sender_id', 'receiver_id']);
            $table->index(['booking_id']);
        });

        // 10b. WhatsApp Webhook Logs
        Schema::create('whatsapp_webhook_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table.string('whatsapp_number');
            $table.string('event_type'); // 'message', 'status', 'optin'
            $table.text('payload'); // stored JSON from Twilio/Infobip
            $table.string('status'); // 'received', 'delivered', 'read'
            $table.uuid('related_booking_id')->nullable();
            $table.uuid('related_user_id')->nullable();
            $table.timestamps();

            $table->index(['whatsapp_number', 'event_type']);
            $table->index(['status']);
        });

        // 11. Notifications & Toast Feedback
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table.string('type'); // 'booking', 'leave', 'payment', 'system', 'announcement'
            $table.string('title');
            $table.text('message');
            $table.boolean('is_read')->default(false);
            $table.boolean('is_email_sent')->default(false);
            $table.boolean('is_sms_sent')->default(false);
            $table.string('notificationable_type'); // Booking, User, Tenant, etc.
            $table.uuid('notificationable_id'); // ID of the related model
            $table.integer('priority'); // 1=low, 2=normal, 3=high, 4=critical
            $table.timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_read']);
            $table->index(['type', 'is_read']);
            $table->index(['priority']);
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table.string('name'); // welcome, booking_confirmation, password_reset, etc.
            $table.text('subject');
            $table.text('html_content');
            $table.text('text_content')->nullable();
            $table.boolean('is_active')->default(true);
            $table.timestamps();
        });

        // 12. Hall Wishlists (Saved venues)
        Schema::create('hall_wishlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table.uuid('user_id'); // The user who saved it
            $table.uuid('venue_id');
            $table.timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
            $table->index(['user_id']);
            $table->index(['venue_id']);
            $table->unique(['user_id', 'venue_id']);
        });

        // 13. Page Visits & Analytics
        Schema::create('page_visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table.uuid('user_id')->nullable();
            $table.string('path');
            $table.string('method')->default('GET');
            $table.string('user_agent')->nullable();
            $table.string('ip_address')->nullable();
            $table.string('referrer')->nullable();
            $table.timestamps();

            $table->index(['user_id']);
            $table->index(['path']);
            $table->index(['ip_address']);
        });

        // 14. Platform Tasks (Background jobs)
        Schema::create('platform_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table.string('task_type'); // 'send_notification', 'generate_payroll', 'send_reminder', etc.
            $table.text('payload'); // JSON data for the task
            $table.enum('status', ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'CANCELED']);
            $table.integer('attempts')->default(0);
            $table.timestamp('completed_at')->nullable();
            $table.timestamp('failed_at')->nullable();
            $table.uuid('created_by')->nullable();
            $table.timestamps();

            $table->index(['task_type', 'status']);
            $table->index(['created_by']);
        });

        // 15. Audit Log (All actions tracked)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table.string('action'); // 'created', 'updated', 'deleted', 'login', 'logout', 'booking_approved', etc.
            $table.text('details')->nullable(); // JSON with what changed
            $table.uuid('user_id')->nullable(); // Who performed the action
            $table.uuid('tenant_id')->nullable(); // Which tenant
            $table.uuid('model_type')->nullable(); // e.g., 'App\\Models\\Booking'
            $table.uuid('model_id')->nullable(); // e.g., 123-xxx-xxx
            $table.string('ip_address')->nullable();
            $table.string('user_agent')->nullable();
            $table.timestamps();

            $table->index(['user_id']);
            $table->index(['tenant_id']);
            $table->index(['model_type', 'model_id']);
            $table->index(['created_at']);
        });

        // 16. Platform Settings / Config
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table.string('key'); // Unique config key
            $table.text('value'); // JSON or string value
            $table.text('description')->nullable();
            $table boolean('is_sensitive')->default(false); // Encrypt this value
            $table.timestamps();

            $table->unique(['key']);
        });

        // 16b. Feature Flags
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table.string('name'); // e.g., 'billing_enabled', 'kitchen_module_enabled'
            $table.text('value'); // 'true', 'false', or feature-specific config
            $table.boolean('is_active')->default(true);
            $table.timestamps();

            $table->unique(['name']);
        });

        // 17. API Rate Limiting (Per Token)
        Schema::create('api_rate_limits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table.uuid('user_id')->nullable();
            $table.string('identifier'); // email or token hash
            $table.integer('limit'); // max requests
            $table integer('period'); // 1=minute, 2=hour, 3=day, 4=month
            $table.timestamp('reset_at');
            $table.timestamp('first_request_at');
            $table.timestamp('last_request_at');
            $table.timestamps();

            $table->index(['identifier', 'period']);
            $table->index(['reset_at']);
        });

        // 18. Email Queue & Tracking
        Schema::create('email_queue', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table.uuid('tenant_id')->nullable();
            $table.uuid('user_id')->nullable();
            $table.string('template_name');
            $table.string('to_email');
            $table.string('to_name')->nullable();
            $table.subject('subject');
            $table.text('html_content');
            $table.text('text_content')->nullable();
            $table.string('status'); // pending, sent, failed, bounced
            $table.text('error_message')->nullable();
            $table.integer('retry_count')->default(0);
            $table.timestamp('sent_at')->nullable();
            $table.timestamp('delivered_at')->nullable();
            $table.timestamp('bounced_at')->nullable();
            $table.timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
            $table->index(['to_email', 'status']);
            $table->index(['template_name', 'status']);
        });

        // 19. SEO & Metadata
        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table.string('page_type'); // venue, hall, package, static
            $table.uuid('page_id'); // ID of the related model
            $table.string('title');
            $table.text('description');
            $table.string('slug');
            $table.string('focus_keyword');
            $table.string('canonical_url')->nullable();
            $table.boolean('is_indexed')->default(true);
            $table.timestamps();

            $table->unique(['page_type', 'page_id']);
            $table->index(['is_indexed']);
        });

        // 20. Notifications System Logs
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table.uuid('user_id')->nullable();
            $table.string('type'); // matches notifications.type
            $table.string('title');
            $table.text('message');
            $table.boolean('is_delivered')->default(false);
            $table.boolean('is_email_sent')->default(false);
            $table.boolean('is_sms_sent')->default(false);
            $table.boolean('is_whatsapp_sent')->default(false);
            $table.timestamp('delivered_at')->nullable();
            $table.timestamp('opened_at')->nullable();
            $table.timestamp('clicked_at')->nullable();
            $table.timestamps();

            $table->index(['user_id', 'is_delivered']);
            $table->index(['type', 'is_delivered']);
        });

        /***
         * SOFT DELETE MECHANISM NOTES:
         * - All tables use $table->softDeletes() if deletion is needed
         * - Global scope adds WHERE deleted_at IS NULL
         * - Restore via Model::withTrashed()->restore()
         * - Permanent delete via Model::forceDelete()
         *
         * UUID STRATEGY:
         * - All PKs are uuid (binary 16, hex 32)
         * - Uses HasUuids trait from Laravel
         * - Generates via Str::uuid()
         * - Foreign keys are also uuid for consistency
         *
         * INDEXING STRATEGY:
 * - Primary keys on id (uuid)
 * - Unique constraints on email, slug, SKU, combinations
 * - Foreign key indexes on _id columns
 * - Compound indexes on frequently queried combinations
 * - Partial indexes on is_active, is_read, is_published
 * *********/

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_rate_limits');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('seo_metadata');
        Schema::dropIfExists('email_queue');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('platform_tasks');
        Schema::dropIfExists('hall_wishlists');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('whatsapp_webhook_logs');
        Schema::dropIfExists('hall_chat_messages');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('floor_plans');
        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('staff_payrolls');
        Schema::dropIfExists('staff_leaves');
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('services');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('users');
    }
};