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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id(); // id sebagai Primary Key, tipe INT, Auto Increment
            $table->string('judul', 150); // judul proposal
            $table->text('deskripsi'); // deskripsi proposal
            $table->foreignId('ministry_id');
            $table->foreignId('status_id'); // status proposal
            $table->date('tanggal_pengajuan'); // tanggal pengajuan proposal
            $table->string('file_path'); // path file proposal
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
