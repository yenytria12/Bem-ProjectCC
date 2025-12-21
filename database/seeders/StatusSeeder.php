<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Gunakan insertOrIgnore untuk menghindari duplikasi
        DB::table('statuses')->insertOrIgnore([
            ['name' => 'pending_menteri'],
            ['name' => 'pending_sekretaris'],
            ['name' => 'pending_bendahara'],
            ['name' => 'pending_wakil_presiden'],
            ['name' => 'pending_presiden'],
            ['name' => 'approved'],
            ['name' => 'rejected'],
            ['name' => 'revisi'],
        ]);
    }
}
