<?php

namespace App\Filament\Resources\WhatsAppConversations\Pages;

use App\Filament\Resources\WhatsAppConversations\WhatsAppConversationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppConversation extends EditRecord
{
    protected static string $resource = WhatsAppConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
