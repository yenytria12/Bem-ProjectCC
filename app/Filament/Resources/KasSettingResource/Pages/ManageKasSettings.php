<?php

namespace App\Filament\Resources\KasSettingResource\Pages;

use App\Filament\Resources\KasSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageKasSettings extends ManageRecords
{
    protected static string $resource = KasSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
