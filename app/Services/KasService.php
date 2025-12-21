<?php

namespace App\Services;

use App\Models\KasPayment;
use App\Models\KasSetting;
use App\Models\User;
use App\Notifications\KasPaymentSuccessNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KasService
{
    /**
     * Calculate penalty for overdue payment
     * Penalty = days_overdue × penalty_per_day
     */
    public function calculatePenalty(KasPayment $payment): int
    {
        if ($payment->status !== 'overdue') {
            return 0;
        }

        $setting = KasSetting::getActive();
        $deadlineDate = Carbon::create($payment->period_year, $payment->period_month, $setting->deadline_day);
        
        // If already paid, calculate from deadline to paid_at
        // Otherwise calculate from deadline to now
        $endDate = $payment->paid_at ?? Carbon::now();
        
        if ($endDate->lte($deadlineDate)) {
            return 0;
        }

        $daysOverdue = $deadlineDate->diffInDays($endDate);
        
        return $daysOverdue * $setting->penalty_per_day;
    }

    /**
     * Get total amount (amount + penalty)
     */
    public function getTotalAmount(KasPayment $payment): int
    {
        return $payment->amount + $payment->penalty;
    }

    /**
     * Update penalty and total amount for a payment
     */
    public function updatePenaltyAndTotal(KasPayment $payment): KasPayment
    {
        $payment->penalty = $this->calculatePenalty($payment);
        $payment->total_amount = $this->getTotalAmount($payment);
        $payment->save();

        return $payment;
    }

    /**
     * Generate monthly payments for all active users
     */
    public function generateMonthlyPayments(?int $month = null, ?int $year = null): int
    {
        $month = $month ?? Carbon::now()->month;
        $year = $year ?? Carbon::now()->year;
        $setting = KasSetting::getActive();

        $users = User::all();
        $created = 0;

        foreach ($users as $user) {
            // Check if payment already exists for this period
            $exists = KasPayment::where('user_id', $user->id)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->exists();

            if (!$exists) {
                KasPayment::create([
                    'user_id' => $user->id,
                    'period_month' => $month,
                    'period_year' => $year,
                    'amount' => $setting->nominal,
                    'penalty' => 0,
                    'total_amount' => $setting->nominal,
                    'status' => 'pending',
                ]);
                $created++;
            }
        }

        return $created;
    }

    /**
     * Mark overdue payments
     */
    public function markAsOverdue(): int
    {
        $setting = KasSetting::getActive();
        $now = Carbon::now();
        
        // Only mark as overdue if we're past the deadline day
        if ($now->day <= $setting->deadline_day) {
            return 0;
        }

        $updated = KasPayment::where('status', 'pending')
            ->where('period_month', $now->month)
            ->where('period_year', $now->year)
            ->update(['status' => 'overdue']);

        // Also update penalties for all overdue payments
        $overduePayments = KasPayment::where('status', 'overdue')->get();
        foreach ($overduePayments as $payment) {
            $this->updatePenaltyAndTotal($payment);
        }

        return $updated;
    }

    /**
     * Record manual payment by Bendahara
     */
    public function recordManualPayment(KasPayment $payment, User $bendahara, string $notes): bool
    {
        if (empty($notes)) {
            return false;
        }

        return DB::transaction(function () use ($payment, $bendahara, $notes) {
            // Update penalty before marking as paid
            if ($payment->status === 'overdue') {
                $payment->penalty = $this->calculatePenalty($payment);
            }

            $payment->status = 'paid';
            $payment->payment_method = 'manual';
            $payment->paid_at = Carbon::now();
            $payment->processed_by = $bendahara->id;
            $payment->notes = $notes;
            $payment->total_amount = $payment->amount + $payment->penalty;
            
            $saved = $payment->save();

            // Send success notification
            if ($saved && $payment->user) {
                $payment->user->notify(new KasPaymentSuccessNotification($payment));
            }

            return $saved;
        });
    }

    /**
     * Get payment summary for a period
     */
    public function getSummary(?int $month = null, ?int $year = null): array
    {
        $month = $month ?? Carbon::now()->month;
        $year = $year ?? Carbon::now()->year;

        $payments = KasPayment::forPeriod($month, $year)->get();
        $totalUsers = User::count();
        $paidPayments = $payments->where('status', 'paid');

        return [
            'period_month' => $month,
            'period_year' => $year,
            'total_users' => $totalUsers,
            'total_paid' => $paidPayments->count(),
            'total_pending' => $payments->where('status', 'pending')->count(),
            'total_overdue' => $payments->where('status', 'overdue')->count(),
            'total_collected' => $paidPayments->sum('total_amount'),
            'percentage_paid' => $totalUsers > 0 ? round(($paidPayments->count() / $totalUsers) * 100, 2) : 0,
        ];
    }
}
