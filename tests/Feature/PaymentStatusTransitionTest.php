<?php

namespace Tests\Feature;

use App\Models\KasPayment;
use App\Models\KasSetting;
use App\Models\User;
use App\Services\KasService;
use App\Services\MidtransService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        KasSetting::create([
            'nominal' => 50000,
            'deadline_day' => 25,
            'penalty_per_day' => 500,
            'reminder_days_before' => 7,
            'is_active' => true,
        ]);
    }

    /**
     * **Feature: kas-internal, Property 3: Payment Status Transition Validity**
     * **Validates: Requirements 1.3, 1.4, 5.1**
     * 
     * For any KasPayment, status transitions SHALL only follow:
     * - pending → paid
     * - pending → overdue
     * - overdue → paid
     * No other transitions are valid.
     * 
     * @dataProvider validTransitionDataProvider
     */
    public function test_valid_status_transitions(string $fromStatus, string $toStatus): void
    {
        $user = User::factory()->create();

        $payment = KasPayment::create([
            'user_id' => $user->id,
            'period_month' => 12,
            'period_year' => 2025,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => $fromStatus,
        ]);

        // Perform the transition
        $payment->status = $toStatus;
        $payment->save();

        $payment->refresh();
        $this->assertEquals($toStatus, $payment->status);
    }

    /**
     * Data provider for valid transitions
     */
    public static function validTransitionDataProvider(): array
    {
        return [
            'pending_to_paid' => ['pending', 'paid'],
            'pending_to_overdue' => ['pending', 'overdue'],
            'overdue_to_paid' => ['overdue', 'paid'],
        ];
    }

    /**
     * Test that pending payment can transition to paid via Midtrans callback
     */
    public function test_pending_to_paid_via_callback(): void
    {
        $user = User::factory()->create();

        $payment = KasPayment::create([
            'user_id' => $user->id,
            'period_month' => 12,
            'period_year' => 2025,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => 'pending',
            'midtrans_order_id' => 'KAS-TEST-123',
        ]);

        $midtransService = new MidtransService();
        
        $payload = [
            'order_id' => 'KAS-TEST-123',
            'transaction_status' => 'settlement',
            'transaction_id' => 'TRX-123',
            'payment_type' => 'bank_transfer',
        ];

        $result = $midtransService->handleCallback($payload);

        $this->assertTrue($result);
        
        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('midtrans', $payment->payment_method);
        $this->assertNotNull($payment->paid_at);
    }

    /**
     * Test that pending payment transitions to overdue after deadline
     */
    public function test_pending_to_overdue_after_deadline(): void
    {
        $user = User::factory()->create();

        // Set date to after deadline
        Carbon::setTestNow(Carbon::create(2025, 12, 26));

        $payment = KasPayment::create([
            'user_id' => $user->id,
            'period_month' => 12,
            'period_year' => 2025,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => 'pending',
        ]);

        $kasService = new KasService();
        $kasService->markAsOverdue();

        $payment->refresh();
        $this->assertEquals('overdue', $payment->status);

        Carbon::setTestNow();
    }

    /**
     * Test that overdue payment can be paid via manual payment
     */
    public function test_overdue_to_paid_via_manual(): void
    {
        $user = User::factory()->create();
        $bendahara = User::factory()->create();

        $payment = KasPayment::create([
            'user_id' => $user->id,
            'period_month' => 12,
            'period_year' => 2025,
            'amount' => 50000,
            'penalty' => 1000,
            'total_amount' => 51000,
            'status' => 'overdue',
        ]);

        $kasService = new KasService();
        $result = $kasService->recordManualPayment($payment, $bendahara, 'Pembayaran tunai');

        $this->assertTrue($result);
        
        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
    }

    /**
     * Test that already paid payment ignores duplicate callback
     */
    public function test_paid_payment_ignores_duplicate_callback(): void
    {
        $user = User::factory()->create();

        $payment = KasPayment::create([
            'user_id' => $user->id,
            'period_month' => 12,
            'period_year' => 2025,
            'amount' => 50000,
            'penalty' => 0,
            'total_amount' => 50000,
            'status' => 'paid',
            'payment_method' => 'midtrans',
            'midtrans_order_id' => 'KAS-TEST-456',
            'paid_at' => Carbon::now(),
        ]);

        $midtransService = new MidtransService();
        
        $payload = [
            'order_id' => 'KAS-TEST-456',
            'transaction_status' => 'settlement',
            'transaction_id' => 'TRX-456',
            'payment_type' => 'bank_transfer',
        ];

        // Should return true but not change anything
        $result = $midtransService->handleCallback($payload);

        $this->assertTrue($result);
        
        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
    }
}
