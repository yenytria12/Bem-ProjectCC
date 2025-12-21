<?php

namespace App\Notifications;

use App\Models\KasPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KasPaymentSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected KasPayment $payment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->payment->total_amount, 0, ',', '.');
        $method = match ($this->payment->payment_method) {
            'midtrans' => 'Midtrans',
            'manual' => 'Manual/Tunai',
            'cash' => 'Tunai',
            default => $this->payment->payment_method,
        };

        return (new MailMessage)
            ->subject('Pembayaran Kas Berhasil - ' . $this->payment->period_label)
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Pembayaran kas BEM kamu untuk periode ' . $this->payment->period_label . ' telah berhasil.')
            ->line('Detail Pembayaran:')
            ->line('- Nominal: Rp ' . number_format($this->payment->amount, 0, ',', '.'))
            ->line('- Denda: Rp ' . number_format($this->payment->penalty, 0, ',', '.'))
            ->line('- Total: Rp ' . $amount)
            ->line('- Metode: ' . $method)
            ->line('- Tanggal: ' . $this->payment->paid_at->format('d M Y H:i'))
            ->when($this->payment->midtrans_transaction_id, function ($message) {
                return $message->line('- Transaction ID: ' . $this->payment->midtrans_transaction_id);
            })
            ->line('Terima kasih atas pembayarannya!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'kas_payment_success',
            'payment_id' => $this->payment->id,
            'period_label' => $this->payment->period_label,
            'total_amount' => $this->payment->total_amount,
            'payment_method' => $this->payment->payment_method,
            'paid_at' => $this->payment->paid_at->toIso8601String(),
            'message' => 'Pembayaran kas ' . $this->payment->period_label . ' sebesar Rp ' . number_format($this->payment->total_amount, 0, ',', '.') . ' berhasil',
        ];
    }
}
