<?php

namespace App\Filament\Resources\KasPaymentResource\Pages;

use App\Filament\Resources\KasPaymentResource;
use App\Models\KasPayment;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Response;

class ListKasPayments extends ListRecords
{
    protected static string $resource = KasPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()->hasAnyRole(['Super Admin', 'Bendahara']))
                ->action(function () {
                    $payments = KasPayment::with(['user', 'user.ministry', 'processor'])
                        ->orderBy('period_year', 'desc')
                        ->orderBy('period_month', 'desc')
                        ->get();

                    $csvData = [];
                    $csvData[] = [
                        'No',
                        'Nama Anggota',
                        'Email',
                        'Kementerian',
                        'Periode',
                        'Nominal',
                        'Denda',
                        'Total',
                        'Status',
                        'Metode Pembayaran',
                        'Tanggal Bayar',
                        'Diproses Oleh',
                        'Catatan',
                    ];

                    $no = 1;
                    foreach ($payments as $payment) {
                        $csvData[] = [
                            $no++,
                            $payment->user->name ?? '-',
                            $payment->user->email ?? '-',
                            $payment->user->ministry->nama ?? '-',
                            $payment->period_label,
                            $payment->amount,
                            $payment->penalty,
                            $payment->total_amount,
                            match ($payment->status) {
                                'pending' => 'Pending',
                                'paid' => 'Lunas',
                                'overdue' => 'Terlambat',
                                default => $payment->status,
                            },
                            match ($payment->payment_method) {
                                'midtrans' => 'Midtrans',
                                'manual' => 'Manual',
                                'cash' => 'Tunai',
                                default => '-',
                            },
                            $payment->paid_at?->format('d/m/Y H:i') ?? '-',
                            $payment->processor->name ?? '-',
                            $payment->notes ?? '-',
                        ];
                    }

                    $filename = 'laporan-kas-' . now()->format('Y-m-d') . '.csv';
                    
                    $handle = fopen('php://temp', 'r+');
                    foreach ($csvData as $row) {
                        fputcsv($handle, $row);
                    }
                    rewind($handle);
                    $csv = stream_get_contents($handle);
                    fclose($handle);

                    return Response::streamDownload(function () use ($csv) {
                        echo $csv;
                    }, $filename, [
                        'Content-Type' => 'text/csv',
                    ]);
                }),
        ];
    }
}
