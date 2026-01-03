<?php

namespace App\Filament\Widgets;

use App\Models\PageVisit;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class VisitorStatsWidget extends ChartWidget
{
    protected static ?string $heading = 'Statistik Pengunjung';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    /**
     * Only Super Admin can view this widget
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasRole('Super Admin') ?? false;
    }

    protected function getData(): array
    {
        // Get data for last 7 days
        $data = collect();
        $labels = collect();

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels->push($date->format('D, d M'));

            $count = PageVisit::whereDate('visited_at', $date->toDateString())
                ->distinct('ip_address')
                ->count('ip_address');

            $data->push($count);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pengunjung Unik',
                    'data' => $data->toArray(),
                    'fill' => true,
                    'backgroundColor' => 'rgba(211, 47, 47, 0.2)',
                    'borderColor' => 'rgb(211, 47, 47)',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $todayCount = PageVisit::whereDate('visited_at', today())
            ->distinct('ip_address')
            ->count('ip_address');

        $totalCount = PageVisit::distinct('ip_address')->count('ip_address');

        return "Hari ini: {$todayCount} pengunjung | Total: {$totalCount} pengunjung unik";
    }
}
