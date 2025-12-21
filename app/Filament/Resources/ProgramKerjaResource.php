<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramKerjaResource\Pages;
use App\Filament\Resources\ProgramKerjaResource\RelationManagers;
use App\Models\ProgramKerja;
use App\Models\Ministry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProgramKerjaResource extends Resource
{
    protected static ?string $model = ProgramKerja::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Program Kerja';
    protected static ?string $navigationGroup = 'Manajemen Proposal';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Program Kerja')
                    ->schema([
                        Forms\Components\Select::make('ministry_id')
                            ->relationship('ministry', 'nama', function ($query) {
                                $user = auth()->user();
                                // Role tertinggi bisa pilih semua ministry
                                if ($user->hasAnyRole(['Super Admin', 'Presiden BEM', 'Wakil Presiden BEM', 'Sekretaris', 'Bendahara'])) {
                                    return $query;
                                }
                                // Menteri dan Anggota hanya bisa pilih ministry mereka sendiri
                                if ($user->ministry_id) {
                                    return $query->where('id', $user->ministry_id);
                                }
                                return $query->where('id', 0); // Tidak bisa pilih apa-apa
                            })
                            ->label('Kementerian')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(function () {
                                $user = auth()->user();
                                // Auto-set ke ministry user jika bukan role tertinggi
                                if (!$user->hasAnyRole(['Super Admin', 'Presiden BEM', 'Wakil Presiden BEM', 'Sekretaris', 'Bendahara']) && $user->ministry_id) {
                                    return $user->ministry_id;
                                }
                                return null;
                            }),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Penanggung Jawab')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(auth()->id()),
                        Forms\Components\TextInput::make('nama_program')
                            ->required()
                            ->maxLength(255)
                            ->label('Nama Program'),
                        Forms\Components\Textarea::make('deskripsi')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull()
                            ->label('Deskripsi Program'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Detail Program')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->required()
                            ->label('Tanggal Mulai'),
                        Forms\Components\DatePicker::make('tanggal_selesai')
                            ->required()
                            ->label('Tanggal Selesai')
                            ->after('tanggal_mulai'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'disetujui' => 'Disetujui',
                                'berjalan' => 'Sedang Berjalan',
                                'selesai' => 'Selesai',
                                'dibatalkan' => 'Dibatalkan',
                            ])
                            ->required()
                            ->default('draft')
                            ->label('Status'),
                        Forms\Components\TextInput::make('anggaran')
                            ->numeric()
                            ->label('Anggaran (Rp)')
                            ->prefix('Rp')
                            ->placeholder('0'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('catatan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->label('Catatan')
                            ->placeholder('Tambahkan catatan tambahan jika diperlukan'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_program')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Program'),
                Tables\Columns\TextColumn::make('ministry.nama')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->label('Kementerian'),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Penanggung Jawab'),
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Tanggal Mulai'),
                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Tanggal Selesai'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'draft' => 'gray',
                        'disetujui' => 'info',
                        'berjalan' => 'warning',
                        'selesai' => 'success',
                        'dibatalkan' => 'danger',
                        default => 'gray',
                    })
                    ->label('Status'),
                Tables\Columns\TextColumn::make('anggaran')
                    ->money('IDR')
                    ->label('Anggaran'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ministry_id')
                    ->relationship('ministry', 'nama')
                    ->label('Kementerian'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'disetujui' => 'Disetujui',
                        'berjalan' => 'Sedang Berjalan',
                        'selesai' => 'Selesai',
                        'dibatalkan' => 'Dibatalkan',
                    ])
                    ->label('Status'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProgramKerjas::route('/'),
            'create' => Pages\CreateProgramKerja::route('/create'),
            'edit' => Pages\EditProgramKerja::route('/{record}/edit'),
        ];
    }
}
