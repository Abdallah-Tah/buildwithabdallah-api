<?php

namespace App\Filament\Resources\StripeWebhookEvents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StripeWebhookEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('stripe_event_id')
                    ->required(),
                TextInput::make('type')
                    ->required(),
                Toggle::make('livemode')
                    ->required(),
                Textarea::make('payload')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('received'),
                DateTimePicker::make('processed_at'),
                Textarea::make('error_message')
                    ->columnSpanFull(),
            ]);
    }
}
