<?php

namespace App\Filament\Widgets;

use App\Models\KasPayment;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;

class KasReportWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.kas-report-widget';
    
    protected static ?int $sort = 2;
    
    protected int|string|array $columnSpan = 'full';

    public ?array $data = [];

    public ?int $selectedMonth = null;
    public ?int $selectedYear = null;

    public function mount(): void
    {
        $this->selectedMonth = Carbon::now()->month;
        $this->selectedYear = Carbon::now()->year;
        $this->form->fill([
            'month' => $this->selectedMonth,
            'year' => $this->selectedYear,
        ]);
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['Super Admin', 'Bendahara']);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('month')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                        4 => 'April', 5 => 'Mei', 6 => 'Juni',
                        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ])
                    ->default(Carbon::now()->month)
                    ->reactive()
                    ->afterStateUpdated(fn ($state) => $this->selectedMonth = $state),
                Select::make('year')
                    ->label('Tahun')
                    ->options(function () {
                        $years = [];
                        for ($y = 2024; $y <= Carbon::now()->year + 1; $y++) {
                            $years[$y] = $y;
                        }
                        return $years;
                    })
                    ->default(Carbon::now()->year)
                    ->reactive()
                    ->afterStateUpdated(fn ($state) => $this->selectedYear = $state),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function getReportData(): array
    {
        $month = $this->data['month'] ?? Carbon::now()->month;
        $year = $this->data['year'] ?? Carbon::now()->year;

        $payments = KasPayment::where('period_month', $month)
            ->where('period_year', $year)
            ->get();

        $totalCollected = $payments->where('status', 'paid')->sum('total_amount');
        $totalPending = $payments->where('status', 'pending')->sum('total_amount');
        $totalOverdue = $payments->where('status', 'overdue')->sum('total_amount');

        return [
            'month' => $month,
            'year' => $year,
            'total_collected' => $totalCollected,
            'total_pending' => $totalPending,
            'total_overdue' => $totalOverdue,
            'paid_count' => $payments->where('status', 'paid')->count(),
            'pending_count' => $payments->where('status', 'pending')->count(),
            'overdue_count' => $payments->where('status', 'overdue')->count(),
            'total_count' => $payments->count(),
        ];
    }
}
