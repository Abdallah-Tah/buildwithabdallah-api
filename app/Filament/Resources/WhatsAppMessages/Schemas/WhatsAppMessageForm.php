<?php

namespace App\Filament\Resources\WhatsAppMessages\Schemas;

use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WhatsAppMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('whatsapp_conversation_id'),
                TextInput::make('whatsapp_contact_id'),
                Select::make('connected_application_id')
                    ->relationship('connectedApplication', 'name'),
                TextInput::make('meta_message_id'),
                TextInput::make('correlation_id'),
                TextInput::make('idempotency_key'),
                TextInput::make('request_hash'),
                Select::make('direction')
                    ->options(MessageDirection::class)
                    ->required(),
                TextInput::make('message_type')
                    ->required(),
                Select::make('status')
                    ->options(MessageStatus::class)
                    ->required(),
                Textarea::make('text_body_encrypted')
                    ->columnSpanFull(),
                TextInput::make('template_name'),
                TextInput::make('template_language'),
                TextInput::make('media_id'),
                TextInput::make('reply_to_meta_message_id'),
                Textarea::make('request_payload')
                    ->columnSpanFull(),
                Textarea::make('response_payload')
                    ->columnSpanFull(),
                TextInput::make('failure_code'),
                Textarea::make('failure_message')
                    ->columnSpanFull(),
                DateTimePicker::make('sent_at'),
                DateTimePicker::make('delivered_at'),
                DateTimePicker::make('read_at'),
                DateTimePicker::make('failed_at'),
                TextInput::make('provider'),
                TextInput::make('provider_message_id'),
            ]);
    }
}
