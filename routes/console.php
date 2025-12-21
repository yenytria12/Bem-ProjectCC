<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Kas Internal Scheduler
// Generate monthly payments on 1st of each month at 00:01
Schedule::command('kas:generate-monthly')->monthlyOn(1, '00:01');

// Mark overdue payments daily at 00:05 (after 25th)
Schedule::command('kas:mark-overdue')->dailyAt('00:05');

// Send reminders on 18th of each month at 09:00 (7 days before deadline)
Schedule::command('kas:send-reminder')->dailyAt('09:00');
