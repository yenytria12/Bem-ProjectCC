<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KasSettingResource\Pages;
use App\Models\KasSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KasSettingResource extends Resource
{
    protected static ?string $model = KasSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Pengaturan Kas';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $years = [];
        for ($y = 2024; $y <= now()->year + 2; $y++) {
            $years[$y] = $y;
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Pengaturan Kas Internal')
                    ->description('Konfigurasi nominal kas dan deadline pembayaran')
                    ->schema([
                        Forms\Components\TextInput::make('nominal')
                            ->label('Nominal Kas Bulanan')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('50000')
                            ->helperText('Nominal kas yang harus dibayar setiap bulan'),
                        Forms\Components\TextInput::make('deadline_day')
                            ->label('Tanggal Deadline')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(28)
                            ->default(25)
                            ->helperText('Tanggal terakhir pembayaran setiap bulan (1-28)'),
                        Forms\Components\TextInput::make('penalty_per_day')
                            ->label('Denda Per Hari')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(500)
                            ->helperText('Denda keterlambatan per hari setelah deadline'),
                        Forms\Components\TextInput::make('reminder_days_before')
                            ->label('Reminder (Hari Sebelum Deadline)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(14)
                            ->default(7)
                            ->helperText('Kirim reminder berapa hari sebelum deadline'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Periode Aktif')
                    ->description('Tentukan periode bulan yang aktif untuk pembayaran kas. Tagihan akan otomatis dibuat untuk semua anggota.')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Select::make('period_start_month')
                                    ->label('Bulan Mulai')
                                    ->options($months)
                                    ->required()
                                    ->default(1),
                                Forms\Components\Select::make('period_start_year')
                                    ->label('Tahun Mulai')
                                    ->options($years)
                                    ->required()
                                    ->default(now()->year),
                                Forms\Components\Select::make('period_end_month')
                                    ->label('Bulan Selesai')
                                    ->options($months)
                                    ->required()
                                    ->default(12),
                                Forms\Components\Select::make('period_end_year')
                                    ->label('Tahun Selesai')
                                    ->options($years)
                                    ->required()
                                    ->default(now()->year),
                            ]),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktifkan & Generate Tagihan')
                            ->default(true)
                            ->helperText('Saat diaktifkan, tagihan akan otomatis dibuat untuk semua anggota di periode yang dipilih'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('deadline_day')
                    ->label('Deadline')
                    ->formatStateUsing(fn ($state) => "Tanggal {$state}")
                    ->sortable(),
                Tables\Columns\TextColumn::make('penalty_per_day')
                    ->label('Denda/Hari')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('period')
                    ->label('Periode Aktif')
                    ->getStateUsing(function ($record) use ($months) {
                        return $months[$record->period_start_month] . ' ' . $record->period_start_year . 
                               ' - ' . 
                               $months[$record->period_end_month] . ' ' . $record->period_end_year;
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKasSettings::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['Super Admin', 'Bendahara']);
    }
}
