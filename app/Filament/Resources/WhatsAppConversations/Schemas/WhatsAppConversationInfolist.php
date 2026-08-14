<?php

namespace App\Filament\Resources\WhatsAppConversations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsAppConversationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Conversation routing')->schema([
                TextEntry::make('id')->label('Conversation ID')->copyable(),
                TextEntry::make('connectedApplication.name')->label('Application')->placeholder('Unrouted'),
                TextEntry::make('product_slug')->label('Product')->badge()->placeholder('Not selected'),
                TextEntry::make('state')->badge(),
                TextEntry::make('customer_service_window_expires_at')->label('Service window expires')->dateTime()->placeholder('Closed'),
                TextEntry::make('last_incoming_message_at')->dateTime()->placeholder('None'),
                TextEntry::make('last_outgoing_message_at')->dateTime()->placeholder('None'),
                TextEntry::make('routed_at')->dateTime()->placeholder('Not routed'),
                TextEntry::make('closed_at')->dateTime()->placeholder('Open'),
                TextEntry::make('created_at')->dateTime(),
            ])->columns(2),
            Section::make('Privacy')->schema([
                TextEntry::make('privacy_notice')->state('Contact identity and conversation content are encrypted and hidden.'),
            ]),
        ]);
    }
}
