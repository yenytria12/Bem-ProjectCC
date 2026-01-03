<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'kas_setting_id',
        'period_month',
        'period_year',
        'amount',
        'penalty',
        'total_amount',
        'status',
        'payment_method',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'paid_at',
        'processed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'integer',
            'period_year' => 'integer',
            'amount' => 'integer',
            'penalty' => 'integer',
            'total_amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * User yang memiliki pembayaran ini
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Kas setting yang menjadi sumber tagihan ini
     */
    public function kasSetting(): BelongsTo
    {
        return $this->belongsTo(KasSetting::class);
    }

    /**
     * Bendahara yang memproses pembayaran manual
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('period_month', $month)->where('period_year', $year);
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check apakah pembayaran sudah overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'overdue';
    }

    /**
     * Check apakah sudah dibayar
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Get periode dalam format string
     */
    public function getPeriodLabelAttribute(): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
        return $months[$this->period_month] . ' ' . $this->period_year;
    }
}
