<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_categories', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('vendor_categories')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('vendor_categories', 'image')) {
                $table->string('image', 255)->nullable()->after('icon');
            }

            if (! Schema::hasColumn('vendor_categories', 'seo_title')) {
                $table->string('seo_title', 160)->nullable()->after('description');
            }

            if (! Schema::hasColumn('vendor_categories', 'seo_description')) {
                $table->string('seo_description', 255)->nullable()->after('seo_title');
            }
        });

        Schema::table('vendor_categories', function (Blueprint $table) {
            if (! $this->indexExists('vendor_categories', 'vendor_categories_parent_id_sort_index')) {
                $table->index(['parent_id', 'sort'], 'vendor_categories_parent_id_sort_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_categories', function (Blueprint $table) {
            if ($this->indexExists('vendor_categories', 'vendor_categories_parent_id_sort_index')) {
                $table->dropIndex('vendor_categories_parent_id_sort_index');
            }
        });

        Schema::table('vendor_categories', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_categories', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }

            foreach (['image', 'seo_title', 'seo_description'] as $column) {
                if (Schema::hasColumn('vendor_categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->pluck('name')->contains($index);
    }
};
