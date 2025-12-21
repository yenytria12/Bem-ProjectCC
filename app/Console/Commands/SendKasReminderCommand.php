<?php

namespace App\Console\Commands;

use App\Models\KasPayment;
use App\Models\KasSetting;
use App\Notifications\KasReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendKasReminderCommand extends Command
{
    protected $signature = 'kas:send-reminder';

    protected $description = 'Send payment reminder to unpaid members';

    public function handle(): int
    {
        $setting = KasSetting::getActive();
        $now = Carbon::now();
        
        // Check if today is reminder day (deadline - reminder_days_before)
        $reminderDay = $setting->deadline_day - $setting->reminder_days_before;
        
        if ($now->day !== $reminderDay) {
            $this->info("Today is not reminder day (day {$reminderDay}). Skipping.");
            return Command::SUCCESS;
        }

        $this->info('Sending payment reminders...');

        $unpaidPayments = KasPayment::where('status', 'pending')
            ->where('period_month', $now->month)
            ->where('period_year', $now->year)
            ->with('user')
            ->get();

        $sent = 0;
        foreach ($unpaidPayments as $payment) {
            if ($payment->user) {
                $payment->user->notify(new KasReminderNotification($payment, $setting));
                $sent++;
            }
        }

        $this->info("Sent {$sent} reminder notifications.");

        return Command::SUCCESS;
    }
}
