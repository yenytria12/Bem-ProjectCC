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
        Schema::create('kas_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('nominal')->default(0)->comment('Nominal kas bulanan dalam Rupiah');
            $table->integer('deadline_day')->default(25)->comment('Tanggal deadline pembayaran');
            $table->integer('penalty_per_day')->default(500)->comment('Denda per hari keterlambatan');
            $table->integer('reminder_days_before')->default(7)->comment('Hari sebelum deadline untuk reminder');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kas_settings');
    }
};
