<?php

namespace App\Filament\Resources\MinistryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProgramKerjasRelationManager extends RelationManager
{
    protected static string $relationship = 'programKerjas';

    protected static ?string $title = 'Program Kerja';

    protected static ?string $label = 'Program Kerja';

    protected static ?string $pluralLabel = 'Program Kerja';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_program')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Program'),
                Forms\Components\Textarea::make('deskripsi')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull()
                    ->label('Deskripsi'),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name', fn (Builder $query) => 
                        $query->where('ministry_id', $this->getOwnerRecord()->id)
                    )
                    ->label('Penanggung Jawab')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->default(fn () => $this->getOwnerRecord()->users()->whereHas('roles', fn($q) => $q->where('name', 'Menteri'))->first()?->id),
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
                Forms\Components\Textarea::make('catatan')
                    ->rows(3)
                    ->columnSpanFull()
                    ->label('Catatan'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_program')
            ->columns([
                Tables\Columns\TextColumn::make('nama_program')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Program'),
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
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'disetujui' => 'info',
                        'berjalan' => 'warning',
                        'selesai' => 'success',
                        'dibatalkan' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'disetujui' => 'Disetujui',
                        'berjalan' => 'Sedang Berjalan',
                        'selesai' => 'Selesai',
                        'dibatalkan' => 'Dibatalkan',
                        default => $state,
                    })
                    ->label('Status'),
                Tables\Columns\TextColumn::make('anggaran')
                    ->money('IDR')
                    ->label('Anggaran')
                    ->sortable(),
            ])
            ->filters([
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
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal_mulai', 'desc');
    }
}
