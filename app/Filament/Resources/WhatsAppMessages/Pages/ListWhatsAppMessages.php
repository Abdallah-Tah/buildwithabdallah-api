<?php

namespace App\Filament\Resources\WhatsAppMessages\Pages;

use App\Filament\Resources\WhatsAppMessages\WhatsAppMessageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppMessages extends ListRecords
{
    protected static string $resource = WhatsAppMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
