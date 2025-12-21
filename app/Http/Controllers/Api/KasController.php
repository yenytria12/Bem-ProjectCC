<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KasPayment;
use App\Models\KasSetting;
use App\Services\KasService;
use App\Services\MidtransService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KasController extends Controller
{
    protected KasService $kasService;
    protected MidtransService $midtransService;

    public function __construct(KasService $kasService, MidtransService $midtransService)
    {
        $this->kasService = $kasService;
        $this->midtransService = $midtransService;
    }

    /**
     * Get current month payment status
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;

        $payment = KasPayment::where('user_id', $user->id)
            ->forPeriod($month, $year)
            ->first();

        $setting = KasSetting::getActive();

        if (!$payment) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_payment' => false,
                    'period_month' => $month,
                    'period_year' => $year,
                    'nominal' => $setting->nominal,
                    'deadline_day' => $setting->deadline_day,
                ],
            ]);
        }

        // Update penalty if overdue
        if ($payment->status === 'overdue') {
            $this->kasService->updatePenaltyAndTotal($payment);
            $payment->refresh();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'has_payment' => true,
                'payment' => [
                    'id' => $payment->id,
                    'period_month' => $payment->period_month,
                    'period_year' => $payment->period_year,
                    'period_label' => $payment->period_label,
                    'amount' => $payment->amount,
                    'penalty' => $payment->penalty,
                    'total_amount' => $payment->total_amount,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                ],
                'deadline_day' => $setting->deadline_day,
            ],
        ]);
    }

    /**
     * Initiate payment via Midtrans
     */
    public function pay(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'payment_id' => 'required|exists:kas_payments,id',
        ]);

        $payment = KasPayment::where('id', $request->payment_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran tidak ditemukan',
            ], 404);
        }

        if ($payment->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran sudah lunas',
            ], 400);
        }

        // Update penalty if overdue
        if ($payment->status === 'overdue') {
            $this->kasService->updatePenaltyAndTotal($payment);
            $payment->refresh();
        }

        $result = $this->midtransService->createTransaction($payment);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat transaksi: ' . ($result['message'] ?? 'Unknown error'),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'snap_token' => $result['snap_token'],
                'order_id' => $result['order_id'],
                'payment' => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'penalty' => $payment->penalty,
                    'total_amount' => $payment->total_amount,
                ],
            ],
        ]);
    }

    /**
     * Handle Midtrans callback/notification
     */
    public function callback(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Verify signature
        if (!$this->midtransService->verifySignature($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 403);
        }

        $result = $this->midtransService->handleCallback($payload);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process callback',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Callback processed',
        ]);
    }

    /**
     * Get payment history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $payments = KasPayment::where('user_id', $user->id)
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'period_month' => $payment->period_month,
                    'period_year' => $payment->period_year,
                    'period_label' => $payment->period_label,
                    'amount' => $payment->amount,
                    'penalty' => $payment->penalty,
                    'total_amount' => $payment->total_amount,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'midtrans_order_id' => $payment->midtrans_order_id,
                    'midtrans_transaction_id' => $payment->midtrans_transaction_id,
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                    'created_at' => $payment->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }
}
