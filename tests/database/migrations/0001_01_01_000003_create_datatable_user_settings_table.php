<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration creates the complete table for testing
        // It includes all columns from the original + additional migrations
        // to avoid conflicts with the package migrations
        if (Schema::hasTable('datatable_user_settings')) {
            // If table exists (from package migrations), drop and recreate
            Schema::dropIfExists('datatable_user_settings');
        }

        Schema::create('datatable_user_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('component')->index();
            $table->string('cache_key')->nullable()->index();
            $table->json('settings');
            $table->morphs('authenticatable', 'datatable_authenticatable');
            $table->boolean('is_permanent')->default(false);
            $table->boolean('is_layout')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datatable_user_settings');
    }
};
