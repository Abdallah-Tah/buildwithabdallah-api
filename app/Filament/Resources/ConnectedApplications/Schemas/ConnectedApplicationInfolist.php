<?php

namespace App\Filament\Resources\ConnectedApplications\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ConnectedApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Application identity')->schema([
                TextEntry::make('name'),
                TextEntry::make('slug')->badge(),
                IconEntry::make('enabled')->boolean(),
                TextEntry::make('last_event_delivered_at')->dateTime()->placeholder('No delivery yet'),
            ])->columns(2),
            Section::make('Delivery configuration')->schema([
                TextEntry::make('webhook_url')->label('WhatsApp webhook')->url(fn ($state) => $state)->openUrlInNewTab()->placeholder('Not configured'),
                TextEntry::make('metadata.billing_webhook_url')->label('Billing webhook')->url(fn ($state) => $state)->openUrlInNewTab()->placeholder('Not configured'),
                TextEntry::make('allowed_ip_ranges')->formatStateUsing(fn ($state) => blank($state) ? 'Any signed request' : implode(', ', $state))->columnSpanFull(),
            ])->columns(2),
            Section::make('Security')->schema([
                TextEntry::make('security_notice')->state('Signing secrets are encrypted and intentionally hidden from this panel.'),
            ]),
        ]);
    }
}
