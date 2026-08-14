<?php

namespace App\Filament\Resources\WhatsAppWebhookEvents\Pages;

use App\Filament\Resources\WhatsAppWebhookEvents\WhatsAppWebhookEventResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsAppWebhookEvent extends ViewRecord
{
    protected static string $resource = WhatsAppWebhookEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
