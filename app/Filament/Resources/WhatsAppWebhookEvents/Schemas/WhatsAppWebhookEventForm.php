<?php

namespace App\Filament\Resources\WhatsAppWebhookEvents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WhatsAppWebhookEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('payload_hash')
                    ->required(),
                TextInput::make('object_type'),
                TextInput::make('event_type'),
                Textarea::make('raw_payload')
                    ->columnSpanFull(),
                DateTimePicker::make('received_at')
                    ->required(),
                DateTimePicker::make('processing_started_at'),
                DateTimePicker::make('processed_at'),
                DateTimePicker::make('failed_at'),
                TextInput::make('attempt_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('processing_error')
                    ->columnSpanFull(),
                TextInput::make('provider')
                    ->required()
                    ->default('meta'),
            ]);
    }
}
