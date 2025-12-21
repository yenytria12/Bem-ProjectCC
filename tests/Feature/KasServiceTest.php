<?php

namespace Tests\Feature;

use App\Models\KasPayment;
use App\Models\KasSetting;
use App\Models\User;
use App\Services\KasService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KasServiceTest extends TestCase
{
    use RefreshDatabase;

    protected KasService $kasService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kasService = new KasService();
    }

    /**
     * **Feature: kas-internal, Property 1: Penalty Calculation Consistency**
     * **Validates: Requirements 5.4, 5.5**
     * 
     * For any overdue KasPayment, the penalty amount SHALL equal (days_overdue × penalty_per_day)
     * 
     * @dataProvider penaltyCalculationDataProvider
     */
    public function test_penalty_calculation_consistency(int $daysOverdue): void
    {
        $penaltyPerDay = 500;
        
        // Create setting with known penalty
        KasSetting::create([
            'nominal' => 50000,
            'deadline_day' => 25,
            'penalty_per_day' => $penaltyPerDay,
            'reminder_days_before' => 7,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        
        // Set a fixed date for testing
        $now = Carbon::create(2025, 12, 25 + $daysOverdue);
        Carbon::setTestNow($now);

        $payment = KasPayment::create([
            'user_id' => $user->id,
            'period_month' => 12,
            'period_year' => 2025,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => 'overdue',
        ]);

        $calculatedPenalty = $this->kasService->calculatePenalty($payment);
        $expectedPenalty = $daysOverdue * $penaltyPerDay;

        $this->assertEquals($expectedPenalty, $calculatedPenalty);

        Carbon::setTestNow(); // Reset
    }

    /**
     * Data provider for penalty calculation test
     */
    public static function penaltyCalculationDataProvider(): array
    {
        $data = [];
        // Test various overdue days from 1 to 30
        for ($i = 1; $i <= 30; $i++) {
            $data["days_overdue_{$i}"] = [$i];
        }
        return $data;
    }

    /**
     * Test penalty is zero for non-overdue payments
     */
    public function test_penalty_is_zero_for_pending_payment(): void
    {
        KasSetting::create([
            'nominal' => 50000,
            'deadline_day' => 25,
            'penalty_per_day' => 500,
            'reminder_days_before' => 7,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        
        $payment = KasPayment::create([
            'user_id' => $user->id,
            'period_month' => 12,
            'period_year' => 2025,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => 'pending',
        ]);

        $calculatedPenalty = $this->kasService->calculatePenalty($payment);

        $this->assertEquals(0, $calculatedPenalty);
    }

    /**
     * **Feature: kas-internal, Property 6: Monthly Payment Generation Completeness**
     * **Validates: Requirements 5.2**
     * 
     * For any month when generateMonthlyPayments() is executed, every active user 
     * SHALL have exactly one KasPayment record for that period.
     * 
     * @dataProvider monthlyPaymentGenerationDataProvider
     */
    public function test_monthly_payment_generation_completeness(int $userCount): void
    {
        KasSetting::create([
            'nominal' => 50000,
            'deadline_day' => 25,
            'penalty_per_day' => 500,
            'reminder_days_before' => 7,
            'is_active' => true,
        ]);

        // Create random number of users
        User::factory()->count($userCount)->create();

        $month = rand(1, 12);
        $year = rand(2024, 2030);

        // Generate payments
        $this->kasService->generateMonthlyPayments($month, $year);

        // Verify every user has exactly one payment for this period
        $users = User::all();
        foreach ($users as $user) {
            $paymentCount = KasPayment::where('user_id', $user->id)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->count();

            $this->assertEquals(1, $paymentCount, "User {$user->id} should have exactly 1 payment");
        }

        // Verify total payments equals total users
        $totalPayments = KasPayment::forPeriod($month, $year)->count();
        $this->assertEquals($userCount, $totalPayments);
    }

    /**
     * Data provider for monthly payment generation test
     */
    public static function monthlyPaymentGenerationDataProvider(): array
    {
        return [
            'one_user' => [1],
            'five_users' => [5],
            'ten_users' => [10],
            'twenty_users' => [20],
        ];
    }

    /**
     * Test that generating payments twice doesn't create duplicates
     */
    public function test_generate_monthly_payments_no_duplicates(): void
    {
        KasSetting::create([
            'nominal' => 50000,
            'deadline_day' => 25,
            'penalty_per_day' => 500,
            'reminder_days_before' => 7,
            'is_active' => true,
        ]);

        User::factory()->count(5)->create();

        // Generate payments twice
        $this->kasService->generateMonthlyPayments(12, 2025);
        $this->kasService->generateMonthlyPayments(12, 2025);

        // Should still only have 5 payments
        $totalPayments = KasPayment::forPeriod(12, 2025)->count();
        $this->assertEquals(5, $totalPayments);
    }

    /**
     * **Feature: kas-internal, Property 5: Manual Payment Audit Trail**
     * **Validates: Requirements 6.2, 6.3**
     * 
     * For any KasPayment with payment_method = 'manual', processed_by SHALL NOT be null 
     * and notes SHALL NOT be empty.
     * 
     * @dataProvider manualPaymentDataProvider
     */
    public function test_manual_payment_audit_trail(string $notes): void
    {
        KasSetting::create([
            'nominal' => 50000,
            'deadline_day' => 25,
            'penalty_per_day' => 500,
            'reminder_days_before' => 7,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $bendahara = User::factory()->create();

        $payment = KasPayment::create([
            'user_id' => $user->id,
            'period_month' => 12,
            'period_year' => 2025,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => 'pending',
        ]);

        $result = $this->kasService->recordManualPayment($payment, $bendahara, $notes);

        $this->assertTrue($result);
        
        $payment->refresh();
        
        // Verify audit trail requirements
        $this->assertEquals('manual', $payment->payment_method);
        $this->assertNotNull($payment->processed_by);
        $this->assertEquals($bendahara->id, $payment->processed_by);
        $this->assertNotEmpty($payment->notes);
        $this->assertEquals($notes, $payment->notes);
        $this->assertEquals('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    /**
     * Data provider for manual payment test
     */
    public static function manualPaymentDataProvider(): array
    {
        return [
            'simple_note' => ['Pembayaran tunai'],
            'detailed_note' => ['Pembayaran tunai di kantor BEM tanggal 20 Desember 2025'],
            'with_reference' => ['Transfer via rekening BEM - Ref: TRX123456'],
        ];
    }

    /**
     * Test manual payment fails without notes
     */
    public function test_manual_payment_fails_without_notes(): void
    {
        KasSetting::create([
            'nominal' => 50000,
            'deadline_day' => 25,
            'penalty_per_day' => 500,
            'reminder_days_before' => 7,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $bendahara = User::factory()->create();

        $payment = KasPayment::create([
            'user_id' => $user->id,
            'period_month' => 12,
            'period_year' => 2025,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => 'pending',
        ]);

        $result = $this->kasService->recordManualPayment($payment, $bendahara, '');

        $this->assertFalse($result);
        
        $payment->refresh();
        $this->assertEquals('pending', $payment->status);
        $this->assertNull($payment->processed_by);
    }
}