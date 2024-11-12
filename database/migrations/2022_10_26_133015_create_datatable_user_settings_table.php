<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('datatable_user_settings')) {
            return;
        }

        Schema::create('datatable_user_settings', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('component')->index();
            $table->json('settings');
            $table->morphs('authenticatable', 'datatable_authenticatable');
            $table->boolean('is_permanent')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datatable_user_settings');
    }
};
