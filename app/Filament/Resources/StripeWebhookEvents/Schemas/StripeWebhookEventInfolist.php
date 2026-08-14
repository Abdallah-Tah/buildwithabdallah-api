<?php

namespace App\Filament\Resources\StripeWebhookEvents\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StripeWebhookEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Stripe event')->schema([
                TextEntry::make('stripe_event_id')->label('Event ID')->copyable(),
                TextEntry::make('type')->badge(),
                TextEntry::make('status')->badge(),
                IconEntry::make('livemode')->label('Live mode')->boolean(),
                TextEntry::make('processed_at')->dateTime()->placeholder('Not processed'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('error_message')->label('Processing error')->placeholder('None')->columnSpanFull(),
            ])->columns(2),
            Section::make('Security')->schema([
                TextEntry::make('payload_notice')->state('The signed Stripe payload is encrypted and intentionally hidden.'),
            ]),
        ]);
    }
}
