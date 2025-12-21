<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'nominal',
        'deadline_day',
        'penalty_per_day',
        'reminder_days_before',
        'is_active',
        'period_start_month',
        'period_start_year',
        'period_end_month',
        'period_end_year',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'deadline_day' => 'integer',
            'penalty_per_day' => 'integer',
            'reminder_days_before' => 'integer',
            'is_active' => 'boolean',
            'period_start_month' => 'integer',
            'period_start_year' => 'integer',
            'period_end_month' => 'integer',
            'period_end_year' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Auto generate payments when setting is saved
        static::saved(function (KasSetting $setting) {
            if ($setting->is_active) {
                $setting->generatePaymentsForPeriod();
            }
        });
    }

    /**
     * Get the active setting or create default one
     */
    public static function getActive(): self
    {
        return self::where('is_active', true)->first() ?? self::create([
            'nominal' => 0,
            'deadline_day' => 25,
            'penalty_per_day' => 500,
            'reminder_days_before' => 7,
            'is_active' => true,
            'period_start_month' => 1,
            'period_start_year' => now()->year,
            'period_end_month' => 12,
            'period_end_year' => now()->year,
        ]);
    }

    /**
     * Generate payments for all users in the active period
     * Super Admin is excluded from kas payments
     */
    public function generatePaymentsForPeriod(): int
    {
        // Exclude Super Admin from kas payments
        $users = User::whereDoesntHave('roles', function ($query) {
            $query->where('name', 'Super Admin');
        })->get();
        
        $created = 0;

        // Loop through all months in the period
        $startDate = \Carbon\Carbon::create($this->period_start_year, $this->period_start_month, 1);
        $endDate = \Carbon\Carbon::create($this->period_end_year, $this->period_end_month, 1);

        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            $month = $currentDate->month;
            $year = $currentDate->year;

            foreach ($users as $user) {
                // Check if payment already exists
                $exists = KasPayment::where('user_id', $user->id)
                    ->where('period_month', $month)
                    ->where('period_year', $year)
                    ->exists();

                if (!$exists) {
                    KasPayment::create([
                        'user_id' => $user->id,
                        'period_month' => $month,
                        'period_year' => $year,
                        'amount' => $this->nominal,
                        'penalty' => 0,
                        'total_amount' => $this->nominal,
                        'status' => 'pending',
                    ]);
                    $created++;
                }
            }

            $currentDate->addMonth();
        }

        return $created;
    }
}
