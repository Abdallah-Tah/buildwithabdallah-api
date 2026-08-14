<?php

namespace App\Filament\Resources\WhatsAppWebhookEvents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsAppWebhookEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Provider webhook')->schema([
                TextEntry::make('provider')->badge(),
                TextEntry::make('event_type')->badge()->placeholder('Unknown'),
                TextEntry::make('object_type')->placeholder('Unknown'),
                TextEntry::make('received_at')->dateTime(),
                TextEntry::make('processed_at')->dateTime()->placeholder('Not processed'),
                TextEntry::make('failed_at')->dateTime()->placeholder('Not failed'),
                TextEntry::make('attempt_count')->numeric(),
                TextEntry::make('processing_error')->placeholder('None')->columnSpanFull(),
            ])->columns(2),
            Section::make('Security')->schema([
                TextEntry::make('payload_notice')->state('Personal message payloads are intentionally hidden from the operations panel.'),
            ]),
        ]);
    }
}
