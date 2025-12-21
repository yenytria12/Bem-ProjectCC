<?php

namespace App\Filament\Pages;

use App\Models\KasPayment;
use App\Models\KasSetting;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LaporanKeuangan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Laporan Keuangan';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Laporan Keuangan Kas';
    protected static string $view = 'filament.pages.laporan-keuangan';

    public ?int $filterMonth = null;
    public ?int $filterYear = null;

    protected $queryString = [
        'filterMonth' => ['except' => null, 'as' => 'bulan'],
        'filterYear' => ['except' => null, 'as' => 'tahun'],
    ];

    public function mount(): void
    {
        $this->filterMonth = request()->query('bulan', Carbon::now()->month);
        $this->filterYear = request()->query('tahun', Carbon::now()->year);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['Super Admin', 'Bendahara']);
    }

    public function updatedFilterMonth($value): void
    {
        $this->filterMonth = (int) $value;
        $this->resetTable();
    }

    public function updatedFilterYear($value): void
    {
        $this->filterYear = (int) $value;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                KasPayment::query()
                    ->with(['user.ministry'])
                    ->where('period_month', $this->filterMonth)
                    ->where('period_year', $this->filterYear)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Anggota')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.ministry.nama')
                    ->label('Kementerian')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR'),
                TextColumn::make('penalty')
                    ->label('Denda')
                    ->money('IDR')
                    ->color('danger'),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Belum Bayar',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                        default => $state,
                    }),
                TextColumn::make('paid_at')
                    ->label('Tanggal Bayar')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'midtrans' => 'Midtrans',
                        'manual' => 'Manual',
                        default => '-',
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'midtrans' => 'info',
                        'manual' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Belum Bayar',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                    ]),
            ])
            ->defaultSort('user.name', 'asc');
    }

    public function getSummary(): array
    {
        $payments = KasPayment::query()
            ->where('period_month', $this->filterMonth)
            ->where('period_year', $this->filterYear)
            ->get();

        return [
            'total_anggota' => $payments->count(),
            'sudah_bayar' => $payments->where('status', 'paid')->count(),
            'belum_bayar' => $payments->where('status', 'pending')->count(),
            'terlambat' => $payments->where('status', 'overdue')->count(),
            'total_terkumpul' => $payments->where('status', 'paid')->sum('total_amount'),
            'total_pending' => $payments->whereIn('status', ['pending', 'overdue'])->sum('total_amount'),
            'total_denda' => $payments->where('status', 'paid')->sum('penalty'),
            'persentase' => $payments->count() > 0 
                ? round(($payments->where('status', 'paid')->count() / $payments->count()) * 100, 1) 
                : 0,
        ];
    }

    public function getMonthName(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return ($months[$this->filterMonth] ?? '') . ' ' . $this->filterYear;
    }

    public function getMonths(): array
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    }

    public function getYears(): array
    {
        $years = [];
        for ($y = 2024; $y <= Carbon::now()->year + 1; $y++) {
            $years[$y] = (string) $y;
        }
        return $years;
    }
}
