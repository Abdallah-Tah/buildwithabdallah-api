<?php

namespace App\Filament\Resources\WhatsAppWebhookEvents\Pages;

use App\Filament\Resources\WhatsAppWebhookEvents\WhatsAppWebhookEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppWebhookEvent extends EditRecord
{
    protected static string $resource = WhatsAppWebhookEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
