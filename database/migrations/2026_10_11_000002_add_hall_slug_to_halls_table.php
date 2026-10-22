<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('halls', function (Blueprint $table) {
            $table->string('slug', 120)->unique()->nullable()->after('name');
        });

        // Populate slugs for already-seeded halls (e.g. the-royal-ballroom).
        // Each slug is derived from the hall name, made unique against the same tenant.
        DB::table('halls')->whereNull('slug')->orderBy('tenant_id')->orderBy('name')->chunkById(500, function ($halls) {
            foreach ($halls as $hall) {
                $base = Str::slug($hall->name);
                $slug = $base;
                $counter = 1;
                while (DB::table('halls')->where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$counter++;
                }
                DB::table('halls')->where('id', $hall->id)->update(['slug' => $slug]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('halls', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
