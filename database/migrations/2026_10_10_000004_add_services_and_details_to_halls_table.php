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
        Schema::table('halls', function (Blueprint $table) {
            $table->string('tagline', 200)->nullable()->after('name');
            $table->json('services')->nullable()->after('description');
            $table->string('operating_hours', 255)->nullable()->after('services');
            $table->text('policies')->nullable()->after('operating_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('halls', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'services', 'operating_hours', 'policies']);
        });
    }
};
