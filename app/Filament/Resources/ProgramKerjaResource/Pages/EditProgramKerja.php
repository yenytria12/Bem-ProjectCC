<?php

namespace App\Filament\Resources\ProgramKerjaResource\Pages;

use App\Filament\Resources\ProgramKerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProgramKerja extends EditRecord
{
    protected static string $resource = ProgramKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
