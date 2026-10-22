<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('remember_token');
                }
                if (! Schema::hasColumn('users', 'status')) {
                    $table->string('status', 20)->default('ACTIVE')->after('role'); // ACTIVE, PENDING, SUSPENDED
                }
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (! Schema::hasColumn('bookings', 'crm_stage')) {
                    $table->string('crm_stage', 30)->default('NEW_INQUIRY')->after('status'); // NEW_INQUIRY, SITE_VISIT, QUOTATION, CONFIRMED, CANCELLED
                    $table->index(['tenant_id', 'crm_stage']);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'last_login_at')) {
                    $table->dropColumn('last_login_at');
                }
                if (Schema::hasColumn('users', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasColumn('bookings', 'crm_stage')) {
                    $table->dropIndex(['tenant_id', 'crm_stage']);
                    $table->dropColumn('crm_stage');
                }
            });
        }
    }
};
