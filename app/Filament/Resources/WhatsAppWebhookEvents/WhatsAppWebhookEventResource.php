<?php

namespace App\Filament\Resources\WhatsAppWebhookEvents;

use App\Filament\Resources\WhatsAppWebhookEvents\Pages\CreateWhatsAppWebhookEvent;
use App\Filament\Resources\WhatsAppWebhookEvents\Pages\EditWhatsAppWebhookEvent;
use App\Filament\Resources\WhatsAppWebhookEvents\Pages\ListWhatsAppWebhookEvents;
use App\Filament\Resources\WhatsAppWebhookEvents\Pages\ViewWhatsAppWebhookEvent;
use App\Filament\Resources\WhatsAppWebhookEvents\Schemas\WhatsAppWebhookEventForm;
use App\Filament\Resources\WhatsAppWebhookEvents\Schemas\WhatsAppWebhookEventInfolist;
use App\Filament\Resources\WhatsAppWebhookEvents\Tables\WhatsAppWebhookEventsTable;
use App\Models\WhatsAppWebhookEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WhatsAppWebhookEventResource extends Resource
{
    protected static ?string $model = WhatsAppWebhookEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $recordTitleAttribute = 'event_type';

    protected static ?string $navigationLabel = 'Provider webhooks';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): string
    {
        return 'WhatsApp';
    }

    public static function form(Schema $schema): Schema
    {
        return WhatsAppWebhookEventForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WhatsAppWebhookEventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhatsAppWebhookEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppWebhookEvents::route('/'),
            'create' => CreateWhatsAppWebhookEvent::route('/create'),
            'view' => ViewWhatsAppWebhookEvent::route('/{record}'),
            'edit' => EditWhatsAppWebhookEvent::route('/{record}/edit'),
        ];
    }
}
