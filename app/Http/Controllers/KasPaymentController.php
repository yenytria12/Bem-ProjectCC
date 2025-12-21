<?php

namespace App\Http\Controllers;

use App\Models\KasPayment;
use App\Services\KasService;
use App\Services\MidtransService;
use Illuminate\Http\Request;

class KasPaymentController extends Controller
{
    public function pay(KasPayment $kasPayment)
    {
        // Pastikan user hanya bisa bayar miliknya sendiri atau bendahara
        $user = auth()->user();
        if ($kasPayment->user_id !== $user->id && !$user->hasAnyRole(['Super Admin', 'Bendahara'])) {
            abort(403, 'Unauthorized');
        }

        if ($kasPayment->status === 'paid') {
            return redirect()->route('filament.admin.resources.kas-payments.index')
                ->with('error', 'Pembayaran sudah lunas');
        }

        // Update penalty jika overdue
        if ($kasPayment->status === 'overdue') {
            $kasService = new KasService();
            $kasService->updatePenaltyAndTotal($kasPayment);
            $kasPayment->refresh();
        }

        $midtransService = new MidtransService();
        $result = $midtransService->createTransaction($kasPayment);

        if (!$result['success']) {
            return redirect()->route('filament.admin.resources.kas-payments.index')
                ->with('error', 'Gagal membuat transaksi: ' . ($result['message'] ?? 'Unknown error'));
        }

        return view('kas.pay', [
            'payment' => $kasPayment,
            'snapToken' => $result['snap_token'],
            'clientKey' => config('midtrans.client_key'),
        ]);
    }

    public function callback(Request $request)
    {
        $midtransService = new MidtransService();
        
        $payload = $request->all();
        
        // Verify signature
        if (!$midtransService->verifySignature($payload)) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        $result = $midtransService->handleCallback($payload);

        return response()->json(['success' => $result]);
    }

    public function finish(Request $request)
    {
        // Check and update payment status from Midtrans
        $orderId = $request->get('order_id');
        if ($orderId) {
            $this->checkAndUpdatePayment($orderId);
        }

        return redirect()->route('filament.admin.resources.kas-payments.index')
            ->with('success', 'Pembayaran berhasil! Terima kasih.');
    }

    /**
     * Check payment status from Midtrans and update local database
     */
    private function checkAndUpdatePayment(string $orderId): void
    {
        $payment = KasPayment::where('midtrans_order_id', $orderId)->first();
        
        if (!$payment || $payment->status === 'paid') {
            return;
        }

        $midtransService = new MidtransService();
        $result = $midtransService->getTransactionStatus($orderId);

        if ($result['success'] && isset($result['data'])) {
            $status = $result['data']->transaction_status ?? null;
            
            if (in_array($status, ['capture', 'settlement'])) {
                $payment->update([
                    'status' => 'paid',
                    'payment_method' => 'midtrans',
                    'midtrans_transaction_id' => $result['data']->transaction_id ?? null,
                    'paid_at' => now(),
                ]);
            }
        }
    }

    public function unfinish(Request $request)
    {
        return redirect()->route('filament.admin.resources.kas-payments.index')
            ->with('warning', 'Pembayaran belum selesai. Silakan coba lagi.');
    }

    public function error(Request $request)
    {
        return redirect()->route('filament.admin.resources.kas-payments.index')
            ->with('error', 'Pembayaran gagal. Silakan coba lagi.');
    }
}
