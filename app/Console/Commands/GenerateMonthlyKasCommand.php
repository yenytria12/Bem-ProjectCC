<?php

namespace App\Console\Commands;

use App\Services\KasService;
use Illuminate\Console\Command;

class GenerateMonthlyKasCommand extends Command
{
    protected $signature = 'kas:generate-monthly {--month= : Bulan (1-12)} {--year= : Tahun}';

    protected $description = 'Generate pembayaran kas bulanan untuk semua anggota';

    public function handle(KasService $kasService): int
    {
        $month = $this->option('month');
        $year = $this->option('year');

        $this->info('Generating monthly kas payments...');

        $created = $kasService->generateMonthlyPayments(
            $month ? (int) $month : null,
            $year ? (int) $year : null
        );

        $this->info("Successfully created {$created} payment records.");

        return Command::SUCCESS;
    }
}
