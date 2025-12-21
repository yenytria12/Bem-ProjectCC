<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KasPaymentResource\Pages;
use App\Models\KasPayment;
use App\Models\KasSetting;
use App\Services\KasService;
use App\Services\MidtransService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KasPaymentResource extends Resource
{
    protected static ?string $model = KasPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Pembayaran Kas';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pembayaran')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Anggota')
                            ->disabled(),
                        Forms\Components\TextInput::make('period_label')
                            ->label('Periode')
                            ->disabled(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Nominal')
                            ->prefix('Rp')
                            ->disabled(),
                        Forms\Components\TextInput::make('penalty')
                            ->label('Denda')
                            ->prefix('Rp')
                            ->disabled(),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Bayar')
                            ->prefix('Rp')
                            ->disabled(),
                        Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isBendahara = $user && $user->hasAnyRole(['Super Admin', 'Bendahara']);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Anggota')
                    ->searchable()
                    ->sortable()
                    ->visible($isBendahara),
                Tables\Columns\TextColumn::make('user.ministry.nama')
                    ->label('Kementerian')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->visible($isBendahara),
                Tables\Columns\TextColumn::make('period_label')
                    ->label('Periode')
                    ->sortable(['period_year', 'period_month']),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('penalty')
                    ->label('Denda')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
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
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Tanggal Bayar')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->defaultSort('period_year', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Belum Bayar',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                    ])
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('ministry')
                    ->relationship('user.ministry', 'nama')
                    ->label('Kementerian')
                    ->searchable()
                    ->preload()
                    ->visible($isBendahara),
                Tables\Filters\Filter::make('period')
                    ->form([
                        Forms\Components\Select::make('period_month')
                            ->options([
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                                4 => 'April', 5 => 'Mei', 6 => 'Juni',
                                7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                                10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                            ])
                            ->label('Bulan'),
                        Forms\Components\TextInput::make('period_year')
                            ->numeric()
                            ->label('Tahun')
                            ->default(now()->year),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['period_month'], fn ($q, $month) => $q->where('period_month', $month))
                            ->when($data['period_year'], fn ($q, $year) => $q->where('period_year', $year));
                    }),
            ])
            ->actions([
                // Tombol Bayar via Midtrans - untuk semua user yang belum bayar
                Tables\Actions\Action::make('bayar')
                    ->label('Bayar Sekarang')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(fn (KasPayment $record): bool => $record->status !== 'paid')
                    ->url(fn (KasPayment $record): string => route('kas.pay', $record->id))
                    ->openUrlInNewTab(),

                // Tombol Bayar Manual - hanya untuk Bendahara
                Tables\Actions\Action::make('manual_payment')
                    ->label('Bayar Manual')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(fn (KasPayment $record): bool => 
                        $record->status !== 'paid' && 
                        auth()->user()->hasAnyRole(['Super Admin', 'Bendahara'])
                    )
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Pembayaran')
                            ->required()
                            ->placeholder('Contoh: Pembayaran tunai di kantor BEM')
                            ->helperText('Wajib diisi sebagai bukti pembayaran manual'),
                    ])
                    ->action(function (KasPayment $record, array $data): void {
                        $kasService = new KasService();
                        $kasService->recordManualPayment($record, auth()->user(), $data['notes']);
                        
                        Notification::make()
                            ->title('Pembayaran berhasil dicatat')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran Manual')
                    ->modalDescription('Pastikan anggota sudah membayar secara tunai/transfer sebelum mencatat pembayaran.'),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKasPayments::route('/'),
            'view' => Pages\ViewKasPayment::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Bendahara dan Super Admin bisa lihat semua
        if ($user->hasAnyRole(['Super Admin', 'Bendahara'])) {
            return $query;
        }

        // User lain hanya bisa lihat milik sendiri
        return $query->where('user_id', $user->id);
    }
}
