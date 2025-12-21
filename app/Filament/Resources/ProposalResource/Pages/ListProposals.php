<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use App\Filament\Resources\ProposalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProposals extends ListRecords
{
    protected static string $resource = ProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    protected function getTableQuery(): Builder
    {
        $user = auth()->user();
        
        // Role tertinggi: Super Admin, Presiden BEM, Wakil Presiden BEM, Sekretaris, Bendahara
        // Bisa lihat semua proposal
        if ($user->hasAnyRole(['Super Admin', 'Presiden BEM', 'Wakil Presiden BEM', 'Sekretaris', 'Bendahara'])) {
            return parent::getTableQuery();
        }
        
        // Menteri dan Anggota: hanya bisa lihat proposal dari kementerian mereka
        if ($user->ministry_id) {
            return parent::getTableQuery()
                ->where('ministry_id', $user->ministry_id);
        }
        
        // Jika tidak punya ministry_id, tidak bisa lihat apa-apa
        return parent::getTableQuery()->where('id', 0);
    }
}
