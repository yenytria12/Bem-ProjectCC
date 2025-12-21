<?php

namespace App\Filament\Resources\KasPaymentResource\Pages;

use App\Filament\Resources\KasPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKasPayment extends EditRecord
{
    protected static string $resource = KasPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
