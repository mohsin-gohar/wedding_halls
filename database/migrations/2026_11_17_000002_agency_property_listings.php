<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listing_inquiries')) {
            Schema::create('listing_inquiries', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('hall_id')->constrained('halls')->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('phone', 30);
                $table->string('email', 160)->nullable();
                $table->string('purpose', 20);
                $table->text('message');
                $table->string('status', 20)->default('NEW');
                $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('contacted_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_inquiries');
    }
};
