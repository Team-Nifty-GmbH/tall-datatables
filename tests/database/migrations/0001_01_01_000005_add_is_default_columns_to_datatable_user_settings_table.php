<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('datatable_user_settings', 'is_default_columns')) {
            return;
        }

        Schema::table('datatable_user_settings', function (Blueprint $table): void {
            $table->boolean('is_default_columns')->default(false)->after('is_layout');
        });
    }

    public function down(): void
    {
        Schema::table('datatable_user_settings', function (Blueprint $table): void {
            $table->dropColumn('is_default_columns');
        });
    }
};
