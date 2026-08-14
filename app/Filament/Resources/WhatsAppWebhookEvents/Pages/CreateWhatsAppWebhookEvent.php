<?php

namespace App\Filament\Resources\WhatsAppWebhookEvents\Pages;

use App\Filament\Resources\WhatsAppWebhookEvents\WhatsAppWebhookEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsAppWebhookEvent extends CreateRecord
{
    protected static string $resource = WhatsAppWebhookEventResource::class;
}
