<?php

namespace App\Filament\Resources\WhatsAppWebhookEvents\Pages;

use App\Filament\Resources\WhatsAppWebhookEvents\WhatsAppWebhookEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppWebhookEvents extends ListRecords
{
    protected static string $resource = WhatsAppWebhookEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
