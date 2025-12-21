<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use App\Filament\Resources\ProposalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProposal extends CreateRecord
{
    protected static string $resource = ProposalResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        
        // Jika user adalah Anggota dan status_id belum di-set, set ke "pending_menteri"
        if ($user->hasRole('Anggota') && empty($data['status_id'])) {
            $status = \App\Models\Status::where('name', 'pending_menteri')->first();
            if ($status) {
                $data['status_id'] = $status->id;
            }
        }
        
        return $data;
    }
}
