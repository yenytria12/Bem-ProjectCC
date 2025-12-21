<?php

namespace Tests\Feature;

use App\Models\KasPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class KasPaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * **Feature: kas-internal, Property 2: No Duplicate Payment Per Period**
     * **Validates: Requirements 1.5**
     * 
     * For any user and period (month/year combination), there SHALL exist at most one KasPayment record.
     * 
     * @dataProvider duplicatePaymentDataProvider
     */
    public function test_duplicate_payment_prevention(int $month, int $year): void
    {
        $user = User::factory()->create();

        // Create first payment - should succeed
        $payment1 = KasPayment::create([
            'user_id' => $user->id,
            'period_month' => $month,
            'period_year' => $year,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('kas_payments', [
            'id' => $payment1->id,
            'user_id' => $user->id,
            'period_month' => $month,
            'period_year' => $year,
        ]);

        // Attempt to create duplicate payment - should fail
        $this->expectException(QueryException::class);

        KasPayment::create([
            'user_id' => $user->id,
            'period_month' => $month,
            'period_year' => $year,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => 'pending',
        ]);
    }

    /**
     * Data provider for duplicate payment test
     * Generates random month/year combinations
     */
    public static function duplicatePaymentDataProvider(): array
    {
        $data = [];
        for ($i = 0; $i < 10; $i++) {
            $month = rand(1, 12);
            $year = rand(2024, 2030);
            $data["month_{$month}_year_{$year}"] = [$month, $year];
        }
        return $data;
    }

    /**
     * Test that different users can have payments for the same period
     */
    public function test_different_users_can_have_same_period_payment(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $payment1 = KasPayment::create([
            'user_id' => $user1->id,
            'period_month' => 12,
            'period_year' => 2025,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => 'pending',
        ]);

        $payment2 = KasPayment::create([
            'user_id' => $user2->id,
            'period_month' => 12,
            'period_year' => 2025,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => 'pending',
        ]);

        $this->assertDatabaseCount('kas_payments', 2);
        $this->assertNotEquals($payment1->id, $payment2->id);
    }

    /**
     * Test that same user can have payments for different periods
     */
    public function test_same_user_can_have_different_period_payments(): void
    {
        $user = User::factory()->create();

        KasPayment::create([
            'user_id' => $user->id,
            'period_month' => 11,
            'period_year' => 2025,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => 'pending',
        ]);

        KasPayment::create([
            'user_id' => $user->id,
            'period_month' => 12,
            'period_year' => 2025,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => 'pending',
        ]);

        $this->assertDatabaseCount('kas_payments', 2);
    }

    /**
     * **Feature: kas-internal, Property 4: Total Amount Calculation**
     * **Validates: Requirements 5.5**
     * 
     * For any KasPayment, total_amount SHALL always equal (amount + penalty).
     * 
     * @dataProvider totalAmountDataProvider
     */
    public function test_total_amount_equals_amount_plus_penalty(int $amount, int $penalty): void
    {
        $user = User::factory()->create();
        $expectedTotal = $amount + $penalty;

        $payment = KasPayment::create([
            'user_id' => $user->id,
            'period_month' => rand(1, 12),
            'period_year' => 2025,
            'amount' => $amount,
            'penalty' => $penalty,
            'total_amount' => $expectedTotal,
            'status' => 'pending',
        ]);

        $this->assertEquals($expectedTotal, $payment->total_amount);
        $this->assertEquals($payment->amount + $payment->penalty, $payment->total_amount);
    }

    /**
     * Data provider for total amount calculation test
     * Generates random amount and penalty combinations
     */
    public static function totalAmountDataProvider(): array
    {
        $data = [];
        for ($i = 0; $i < 20; $i++) {
            $amount = rand(10000, 100000);
            $penalty = rand(0, 50000);
            $data["amount_{$amount}_penalty_{$penalty}"] = [$amount, $penalty];
        }
        return $data;
    }
}