<?php

namespace App\Filament\Resources\WhatsAppMessages\Pages;

use App\Filament\Resources\WhatsAppMessages\WhatsAppMessageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsAppMessage extends CreateRecord
{
    protected static string $resource = WhatsAppMessageResource::class;
}
