<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'weekly_off_days')) {
                $table->json('weekly_off_days')->nullable();
            }
        });

        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->string('source', 32)->default('RECORDED')->after('notes');
            $table->boolean('overnight')->default(false)->after('source');
        });

        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('attendance_id')->nullable()->constrained('staff_attendances')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20)->nullable();
            $table->string('old_check_in', 8)->nullable();
            $table->string('new_check_in', 8)->nullable();
            $table->string('old_check_out', 8)->nullable();
            $table->string('new_check_out', 8)->nullable();
            $table->decimal('old_overtime', 5, 2)->nullable();
            $table->decimal('new_overtime', 5, 2)->nullable();
            $table->string('reason', 500);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');

        if (Schema::hasColumn('tenants', 'weekly_off_days')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('weekly_off_days');
            });
        }

        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->dropColumn(['source', 'overnight']);
        });
    }
};
