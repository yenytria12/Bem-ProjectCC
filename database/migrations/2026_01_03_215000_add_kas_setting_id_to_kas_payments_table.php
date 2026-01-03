<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kas_payments', function (Blueprint $table) {
            $table->foreignId('kas_setting_id')
                ->nullable()
                ->after('user_id')
                ->constrained('kas_settings')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kas_payments', function (Blueprint $table) {
            $table->dropForeign(['kas_setting_id']);
            $table->dropColumn('kas_setting_id');
        });
    }
};
