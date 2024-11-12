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
        Schema::table('datatable_user_settings', function (Blueprint $table) {
            $table->boolean('is_layout')->default(false)->after('authenticatable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('datatable_user_settings', function (Blueprint $table) {
            $table->dropColumn('is_layout');
        });
    }
};
