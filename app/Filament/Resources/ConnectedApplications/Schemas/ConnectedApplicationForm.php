<?php

namespace App\Filament\Resources\ConnectedApplications\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ConnectedApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('webhook_url')
                    ->url(),
                Textarea::make('request_signing_secret')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('event_signing_secret')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('enabled')
                    ->required(),
                Textarea::make('allowed_ip_ranges')
                    ->columnSpanFull(),
                Textarea::make('metadata')
                    ->columnSpanFull(),
                DateTimePicker::make('last_event_delivered_at'),
            ]);
    }
}
