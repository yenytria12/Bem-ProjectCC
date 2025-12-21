<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus duplikasi jika ada sebelum menambahkan constraint
        // Compatible with both MySQL and SQLite
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            DB::statement('
                DELETE FROM statuses 
                WHERE id NOT IN (
                    SELECT MIN(id) FROM statuses GROUP BY name
                )
            ');
        } else {
            DB::statement('
                DELETE t1 FROM statuses t1
                INNER JOIN statuses t2 
                WHERE t1.id > t2.id AND t1.name = t2.name
            ');
        }

        // Tambahkan unique constraint pada kolom name
        Schema::table('statuses', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
