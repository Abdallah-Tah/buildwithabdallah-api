<?php

namespace App\Filament\Resources\ApplicationEventDeliveries\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ApplicationEventDeliveryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('event_id')
                    ->required(),
                Select::make('connected_application_id')
                    ->relationship('connectedApplication', 'name')
                    ->required(),
                TextInput::make('whatsapp_message_id'),
                TextInput::make('event_type')
                    ->required(),
                Textarea::make('payload')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('attempt_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('response_status')
                    ->numeric(),
                Textarea::make('last_error')
                    ->columnSpanFull(),
                DateTimePicker::make('delivered_at'),
            ]);
    }
}
