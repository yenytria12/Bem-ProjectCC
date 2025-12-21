<?php

namespace App\Services;

use App\Models\KasPayment;
use App\Notifications\KasPaymentSuccessNotification;
use Carbon\Carbon;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;
use Midtrans\Notification;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Create Midtrans Snap transaction
     */
    public function createTransaction(KasPayment $payment): array
    {
        $orderId = 'KAS-' . $payment->id . '-' . time();
        
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $payment->total_amount,
            ],
            'customer_details' => [
                'first_name' => $payment->user->name,
                'email' => $payment->user->email,
            ],
            'item_details' => [
                [
                    'id' => 'KAS-' . $payment->period_month . '-' . $payment->period_year,
                    'price' => $payment->amount,
                    'quantity' => 1,
                    'name' => 'Kas BEM ' . $payment->period_label,
                ],
            ],
        ];

        // Add penalty as separate item if exists
        if ($payment->penalty > 0) {
            $params['item_details'][] = [
                'id' => 'PENALTY-' . $payment->id,
                'price' => $payment->penalty,
                'quantity' => 1,
                'name' => 'Denda Keterlambatan',
            ];
        }

        try {
            $snapToken = Snap::getSnapToken($params);
            
            // Update payment with order ID
            $payment->update([
                'midtrans_order_id' => $orderId,
            ]);

            return [
                'success' => true,
                'snap_token' => $snapToken,
                'order_id' => $orderId,
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans createTransaction error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle Midtrans payment callback/notification
     */
    public function handleCallback(array $payload): bool
    {
        try {
            $orderId = $payload['order_id'] ?? null;
            $transactionStatus = $payload['transaction_status'] ?? null;
            $transactionId = $payload['transaction_id'] ?? null;
            $paymentType = $payload['payment_type'] ?? null;
            $fraudStatus = $payload['fraud_status'] ?? null;

            if (!$orderId) {
                Log::warning('Midtrans callback: missing order_id');
                return false;
            }

            $payment = KasPayment::where('midtrans_order_id', $orderId)->first();

            if (!$payment) {
                Log::warning('Midtrans callback: payment not found for order_id ' . $orderId);
                return false;
            }

            // Already paid, ignore duplicate callback
            if ($payment->status === 'paid') {
                Log::info('Midtrans callback: payment already paid for order_id ' . $orderId);
                return true;
            }

            // Process based on transaction status
            if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
                // For credit card, check fraud status
                if ($paymentType === 'credit_card' && $fraudStatus !== 'accept') {
                    Log::warning('Midtrans callback: fraud detected for order_id ' . $orderId);
                    return false;
                }

                $payment->update([
                    'status' => 'paid',
                    'payment_method' => 'midtrans',
                    'midtrans_transaction_id' => $transactionId,
                    'paid_at' => Carbon::now(),
                ]);

                // Send success notification
                $payment->refresh();
                if ($payment->user) {
                    $payment->user->notify(new KasPaymentSuccessNotification($payment));
                }

                Log::info('Midtrans callback: payment successful for order_id ' . $orderId);
                return true;
            }

            if (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
                Log::info('Midtrans callback: payment ' . $transactionStatus . ' for order_id ' . $orderId);
                return true;
            }

            if ($transactionStatus === 'pending') {
                Log::info('Midtrans callback: payment pending for order_id ' . $orderId);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Midtrans handleCallback error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get transaction status from Midtrans
     */
    public function getTransactionStatus(string $orderId): array
    {
        try {
            $status = Transaction::status($orderId);
            
            return [
                'success' => true,
                'data' => $status,
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans getTransactionStatus error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify callback signature
     */
    public function verifySignature(array $payload): bool
    {
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        $serverKey = config('midtrans.server_key');
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return $signatureKey === $expectedSignature;
    }
}
