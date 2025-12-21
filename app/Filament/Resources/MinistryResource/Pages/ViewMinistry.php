<?php

namespace App\Filament\Resources\MinistryResource\Pages;

use App\Filament\Resources\MinistryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewMinistry extends ViewRecord
{
    protected static string $resource = MinistryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Kementerian')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama')
                            ->label('Nama Kementerian')
                            ->size('lg')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('deskripsi')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('users_count')
                            ->label('Total Anggota')
                            ->state(fn ($record) => $record->users()->count())
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('menteri_count')
                            ->label('Jumlah Menteri')
                            ->state(fn ($record) => $record->users()->whereHas('roles', fn($query) => $query->where('name', 'Menteri'))->count())
                            ->badge()
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('anggota_count')
                            ->label('Jumlah Anggota')
                            ->state(fn ($record) => $record->users()->whereHas('roles', fn($query) => $query->where('name', 'Anggota'))->count())
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('program_kerjas_count')
                            ->label('Total Program Kerja')
                            ->state(fn ($record) => $record->programKerjas()->count())
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->date('d M Y H:i'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->date('d M Y H:i'),
                    ])
                    ->columns(3),
            ]);
    }
}
