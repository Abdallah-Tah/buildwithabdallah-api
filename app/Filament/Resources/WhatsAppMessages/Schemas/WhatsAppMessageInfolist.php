<?php

namespace App\Filament\Resources\WhatsAppMessages\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsAppMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Message delivery')->schema([
                TextEntry::make('id')->label('Internal ID')->copyable(),
                TextEntry::make('connectedApplication.name')->label('Application')->placeholder('Unrouted'),
                TextEntry::make('provider')->badge()->placeholder('Unknown'),
                TextEntry::make('direction')->badge(),
                TextEntry::make('message_type')->badge(),
                TextEntry::make('status')->badge(),
                TextEntry::make('template_name')->placeholder('Session message'),
                TextEntry::make('template_language')->placeholder('Not applicable'),
                TextEntry::make('provider_message_id')->label('Provider message ID')->copyable()->placeholder('Not assigned'),
                TextEntry::make('correlation_id')->copyable()->placeholder('Not assigned'),
                TextEntry::make('sent_at')->dateTime()->placeholder('Not sent'),
                TextEntry::make('delivered_at')->dateTime()->placeholder('Not delivered'),
                TextEntry::make('read_at')->dateTime()->placeholder('Not read'),
                TextEntry::make('failed_at')->dateTime()->placeholder('Not failed'),
                TextEntry::make('failure_code')->placeholder('None'),
                TextEntry::make('failure_message')->placeholder('None')->columnSpanFull(),
            ])->columns(2),
            Section::make('Privacy')->schema([
                TextEntry::make('privacy_notice')->state('Phone numbers, message text, and provider payloads are encrypted and hidden.'),
            ]),
        ]);
    }
}
