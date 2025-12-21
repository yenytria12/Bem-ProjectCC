<?php

namespace App\Filament\Widgets;

use App\Models\Proposal;
use App\Models\Status;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ProposalChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Statistik Proposal';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '450px';

    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $proposalsQuery = Proposal::query();

        // Filter berdasarkan role
        if ($user->hasRole(['Super Admin', 'Presiden BEM', 'Wakil Presiden BEM', 'Sekretaris', 'Bendahara'])) {
            // Lihat semua proposal
        } elseif ($user->ministry_id) {
            $proposalsQuery->where('ministry_id', $user->ministry_id);
        } else {
            return [
                'datasets' => [['label' => 'Proposal', 'data' => []]],
                'labels' => [],
            ];
        }

        $statuses = Status::all();
        $labels = [];
        $data = [];

        foreach ($statuses as $status) {
            $count = $proposalsQuery->clone()
                ->whereHas('status', fn($q) => $q->where('name', $status->name))
                ->count();
            $label = match ($status->name) {
                'pending_menteri' => 'Review Menteri',
                'pending_sekretaris' => 'Review Sekretaris',
                'pending_bendahara' => 'Review Bendahara',
                'pending_wakil_presiden' => 'Review Wakil Presiden',
                'pending_presiden' => 'Review Presiden',
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak',
                'revisi' => 'Perlu Revisi',
                default => $status->name,
            };
            $labels[] = $label;
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Proposal',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(255, 87, 34, 0.7)', // pending_menteri - orange
                        'rgba(3, 169, 244, 0.7)', // pending_sekretaris - light blue
                        'rgba(255, 152, 0, 0.7)', // pending_bendahara - amber
                        'rgba(156, 39, 176, 0.7)', // pending_wakil_presiden - purple
                        'rgba(63, 81, 181, 0.7)', // pending_presiden - indigo
                        'rgba(76, 175, 80, 0.7)', // approved - green
                        'rgba(244, 67, 54, 0.7)', // rejected - red
                        'rgba(255, 193, 7, 0.7)', // revisi - yellow
                    ],
                    'borderColor' => [
                        'rgba(255, 87, 34, 1)',
                        'rgba(3, 169, 244, 1)',
                        'rgba(255, 152, 0, 1)',
                        'rgba(156, 39, 176, 1)',
                        'rgba(63, 81, 181, 1)',
                        'rgba(76, 175, 80, 1)',
                        'rgba(244, 67, 54, 1)',
                        'rgba(255, 193, 7, 1)',
                    ],
                    'borderWidth' => 2,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 45,
                        'font' => [
                            'size' => 11,
                        ],
                        'padding' => 8,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(255, 255, 255, 0.08)',
                        'lineWidth' => 1,
                    ],
                    'ticks' => [
                        'stepSize' => 1,
                        'precision' => 0,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
            ],
            'maintainAspectRatio' => true,
            'responsive' => true,
            'aspectRatio' => 2.5,
        ];
    }
}

