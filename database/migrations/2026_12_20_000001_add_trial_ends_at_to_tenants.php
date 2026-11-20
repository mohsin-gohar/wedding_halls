<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tenant::hasFreeAccess() and SubscriptionService::isLocked() both read
     * trial_ends_at; the column is missing on installations created before the
     * free-access window was introduced. Adding it is additive only — no
     * existing subscription or historical row is modified.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tenants') || Schema::hasColumn('tenants', 'trial_ends_at')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_expires_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasColumn('tenants', 'trial_ends_at')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('trial_ends_at');
        });
    }
};
