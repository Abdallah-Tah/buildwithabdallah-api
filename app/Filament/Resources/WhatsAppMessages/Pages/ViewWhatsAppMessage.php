<?php

namespace App\Filament\Resources\WhatsAppMessages\Pages;

use App\Filament\Resources\WhatsAppMessages\WhatsAppMessageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsAppMessage extends ViewRecord
{
    protected static string $resource = WhatsAppMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
