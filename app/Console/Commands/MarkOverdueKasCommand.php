<?php

namespace App\Console\Commands;

use App\Services\KasService;
use Illuminate\Console\Command;

class MarkOverdueKasCommand extends Command
{
    protected $signature = 'kas:mark-overdue';

    protected $description = 'Mark unpaid kas payments as overdue after deadline';

    public function handle(KasService $kasService): int
    {
        $this->info('Checking for overdue payments...');

        $updated = $kasService->markAsOverdue();

        $this->info("Marked {$updated} payments as overdue.");

        return Command::SUCCESS;
    }
}
