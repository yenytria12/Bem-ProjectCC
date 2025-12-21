<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Kas - {{ $payment->period_label }}</title>
    @if(config('midtrans.is_production'))
    <script src="https://app.midtrans.com/snap/snap.js" data-client-key="{{ $clientKey }}"></script>
    @else
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ $clientKey }}"></script>
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 min-h-screen">
    <div class="min-h-screen flex flex-col">
        {{-- Header --}}
        <div class="bg-gray-800 border-b border-gray-700 px-6 py-4">
            <div class="max-w-2xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">Pembayaran Kas</span>
                </div>
                <a href="{{ route('filament.admin.resources.kas-payments.index') }}" class="text-gray-400 hover:text-white text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali
                </a>
            </div>
        </div>

        {{-- Content --}}
        <div class="flex-1 flex items-center justify-center p-6">
            <div class="w-full max-w-md">
                {{-- Card --}}
                <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                    {{-- Card Header --}}
                    <div class="bg-gray-750 px-6 py-4 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-white font-semibold text-lg">Kas BEM</h1>
                                <p class="text-gray-400 text-sm">{{ $payment->period_label }}</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                @if($payment->status === 'pending') bg-yellow-500/20 text-yellow-400
                                @elseif($payment->status === 'overdue') bg-red-500/20 text-red-400
                                @else bg-gray-500/20 text-gray-400 @endif">
                                {{ $payment->status === 'pending' ? 'Belum Bayar' : ($payment->status === 'overdue' ? 'Terlambat' : $payment->status) }}
                            </span>
                        </div>
                    </div>

                    {{-- Card Body --}}
                    <div class="p-6 space-y-4">
                        {{-- User Info --}}
                        <div class="flex items-center gap-3 pb-4 border-b border-gray-700">
                            <div class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center">
                                <span class="text-white font-medium">{{ strtoupper(substr($payment->user->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <p class="text-white font-medium">{{ $payment->user->name }}</p>
                                <p class="text-gray-500 text-sm">{{ $payment->user->email }}</p>
                            </div>
                        </div>

                        {{-- Payment Details --}}
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Nominal Kas</span>
                                <span class="text-white">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                            </div>
                            @if($payment->penalty > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-400">Denda Keterlambatan</span>
                                <span class="text-red-400">Rp {{ number_format($payment->penalty, 0, ',', '.') }}</span>
                            </div>
                            @endif
                        </div>

                        {{-- Total --}}
                        <div class="pt-4 border-t border-gray-700">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400 font-medium">Total Bayar</span>
                                <span class="text-2xl font-bold text-orange-500">Rp {{ number_format($payment->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="px-6 pb-6">
                        <button id="pay-button" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-medium py-3 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            Bayar Sekarang
                        </button>
                    </div>
                </div>

                {{-- Info --}}
                <p class="text-center text-gray-500 text-xs mt-4">
                    Pembayaran diproses melalui Midtrans
                </p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('pay-button').addEventListener('click', function() {
            snap.pay('{{ $snapToken }}', {
                onSuccess: function(result) {
                    window.location.href = '{{ route("kas.finish") }}?order_id=' + result.order_id;
                },
                onPending: function(result) {
                    window.location.href = '{{ route("kas.unfinish") }}?order_id=' + result.order_id;
                },
                onError: function(result) {
                    window.location.href = '{{ route("kas.error") }}';
                },
                onClose: function() {}
            });
        });
    </script>
</body>
</html>
