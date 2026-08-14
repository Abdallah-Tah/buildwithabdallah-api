<?php

namespace App\Filament\Resources\WhatsAppConversations\Schemas;

use App\Enums\ConversationState;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WhatsAppConversationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('whatsapp_contact_id')
                    ->required(),
                Select::make('connected_application_id')
                    ->relationship('connectedApplication', 'name'),
                TextInput::make('product_slug'),
                Select::make('state')
                    ->options(ConversationState::class)
                    ->default('new')
                    ->required(),
                DateTimePicker::make('customer_service_window_started_at'),
                DateTimePicker::make('customer_service_window_expires_at'),
                DateTimePicker::make('last_incoming_message_at'),
                DateTimePicker::make('last_outgoing_message_at'),
                DateTimePicker::make('routed_at'),
                DateTimePicker::make('closed_at'),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
