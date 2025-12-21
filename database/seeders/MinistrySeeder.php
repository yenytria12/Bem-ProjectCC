<?php

namespace Database\Seeders;

use App\Models\Ministry;
use Illuminate\Database\Seeder;

class MinistrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ministries = [
            [
                'nama' => 'Kementerian Komunikasi dan Informasi',
                'deskripsi' => 'Bertanggung jawab dalam pengelolaan komunikasi internal dan eksternal BEM, media sosial, dokumentasi, dan publikasi kegiatan.',
            ],
            [
                'nama' => 'Kementerian Riset dan Teknologi',
                'deskripsi' => 'Mengkoordinasikan kegiatan riset, pengembangan teknologi, dan inovasi bagi kemajuan kampus.',
            ],
            [
                'nama' => 'Kementerian Seni dan Budaya',
                'deskripsi' => 'Mengelola kegiatan seni, budaya, dan pengembangan kreativitas mahasiswa.',
            ],
            [
                'nama' => 'Kementerian Sosial dan Politik',
                'deskripsi' => 'Mengkoordinasikan kegiatan sosial, advokasi mahasiswa, dan hubungan dengan pihak eksternal.',
            ],
            [
                'nama' => 'Kementerian Olahraga dan Kesehatan',
                'deskripsi' => 'Mengelola kegiatan olahraga, kesehatan mahasiswa, dan kompetisi olahraga.',
            ],
        ];

        foreach ($ministries as $ministry) {
            Ministry::firstOrCreate(
                ['nama' => $ministry['nama']],
                ['deskripsi' => $ministry['deskripsi']]
            );
        }
    }
}
