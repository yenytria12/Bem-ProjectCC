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
        Schema::table('kas_settings', function (Blueprint $table) {
            $table->integer('period_start_month')->default(1)->after('is_active');
            $table->integer('period_start_year')->default(2025)->after('period_start_month');
            $table->integer('period_end_month')->default(12)->after('period_start_year');
            $table->integer('period_end_year')->default(2025)->after('period_end_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kas_settings', function (Blueprint $table) {
            $table->dropColumn(['period_start_month', 'period_start_year', 'period_end_month', 'period_end_year']);
        });
    }
};
