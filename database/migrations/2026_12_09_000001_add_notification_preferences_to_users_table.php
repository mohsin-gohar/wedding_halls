<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'email_notifications_enabled')) {
                $table->boolean('email_notifications_enabled')->default(true)->after('status');
            }

            if (! Schema::hasColumn('users', 'sms_notifications_enabled')) {
                $table->boolean('sms_notifications_enabled')->default(true)->after('email_notifications_enabled');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'sms_notifications_enabled')) {
                $table->dropColumn('sms_notifications_enabled');
            }

            if (Schema::hasColumn('users', 'email_notifications_enabled')) {
                $table->dropColumn('email_notifications_enabled');
            }
        });
    }
};
