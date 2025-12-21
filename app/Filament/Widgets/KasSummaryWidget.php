<?php

namespace App\Filament\Widgets;

use App\Models\KasPayment;
use App\Models\User;
use App\Services\KasService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KasSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['Super Admin', 'Bendahara']);
    }

    protected function getStats(): array
    {
        $kasService = new KasService();
        $summary = $kasService->getSummary();

        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        $monthName = Carbon::now()->translatedFormat('F Y');

        return [
            Stat::make('Total Terkumpul', 'Rp ' . number_format($summary['total_collected'], 0, ',', '.'))
                ->description("Bulan {$monthName}")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            
            Stat::make('Sudah Bayar', $summary['total_paid'] . ' / ' . $summary['total_users'])
                ->description($summary['percentage_paid'] . '% anggota')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Belum Bayar', $summary['total_pending'])
                ->description('Menunggu pembayaran')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('Terlambat', $summary['total_overdue'])
                ->description('Melewati deadline')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
