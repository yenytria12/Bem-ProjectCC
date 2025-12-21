<?php

namespace App\Notifications;

use App\Models\KasPayment;
use App\Models\KasSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KasReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected KasPayment $payment,
        protected KasSetting $setting
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $deadline = $this->setting->deadline_day;
        $amount = number_format($this->payment->amount, 0, ',', '.');

        return (new MailMessage)
            ->subject('Reminder: Pembayaran Kas BEM ' . $this->payment->period_label)
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Ini adalah pengingat untuk pembayaran kas BEM bulan ' . $this->payment->period_label . '.')
            ->line('Nominal: Rp ' . $amount)
            ->line('Deadline: Tanggal ' . $deadline . ' bulan ini')
            ->line('Denda keterlambatan: Rp ' . number_format($this->setting->penalty_per_day, 0, ',', '.') . ' per hari')
            ->action('Bayar Sekarang', url('/'))
            ->line('Terima kasih atas partisipasinya!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'kas_reminder',
            'payment_id' => $this->payment->id,
            'period_label' => $this->payment->period_label,
            'amount' => $this->payment->amount,
            'deadline_day' => $this->setting->deadline_day,
            'message' => 'Reminder pembayaran kas ' . $this->payment->period_label . ' sebesar Rp ' . number_format($this->payment->amount, 0, ',', '.'),
        ];
    }
}
