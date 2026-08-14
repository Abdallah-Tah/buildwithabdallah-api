<?php

namespace App\Filament\Resources\ApplicationEventDeliveries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApplicationEventDeliveryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Delivery')->schema([
                TextEntry::make('event_id')->label('Event ID')->copyable(),
                TextEntry::make('connectedApplication.name')->label('Application'),
                TextEntry::make('event_type')->badge(),
                TextEntry::make('status')->badge(),
                TextEntry::make('attempt_count')->numeric(),
                TextEntry::make('response_status')->label('HTTP response')->placeholder('None'),
                TextEntry::make('delivered_at')->dateTime()->placeholder('Not delivered'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('last_error')->label('Last error')->placeholder('None')->columnSpanFull(),
            ])->columns(2),
            Section::make('Security')->schema([
                TextEntry::make('payload_notice')->state('The application event payload is hidden because it may contain customer data.'),
            ]),
        ]);
    }
}
