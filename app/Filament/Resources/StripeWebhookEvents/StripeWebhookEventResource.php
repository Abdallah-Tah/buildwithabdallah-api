<?php

namespace App\Filament\Resources\StripeWebhookEvents;

use App\Filament\Resources\StripeWebhookEvents\Pages\CreateStripeWebhookEvent;
use App\Filament\Resources\StripeWebhookEvents\Pages\EditStripeWebhookEvent;
use App\Filament\Resources\StripeWebhookEvents\Pages\ListStripeWebhookEvents;
use App\Filament\Resources\StripeWebhookEvents\Pages\ViewStripeWebhookEvent;
use App\Filament\Resources\StripeWebhookEvents\Schemas\StripeWebhookEventForm;
use App\Filament\Resources\StripeWebhookEvents\Schemas\StripeWebhookEventInfolist;
use App\Filament\Resources\StripeWebhookEvents\Tables\StripeWebhookEventsTable;
use App\Models\StripeWebhookEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StripeWebhookEventResource extends Resource
{
    protected static ?string $model = StripeWebhookEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $recordTitleAttribute = 'type';

    protected static ?string $navigationLabel = 'Stripe events';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): string
    {
        return 'Billing';
    }

    public static function form(Schema $schema): Schema
    {
        return StripeWebhookEventForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StripeWebhookEventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StripeWebhookEventsTable::configure($table);
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
            'index' => ListStripeWebhookEvents::route('/'),
            'create' => CreateStripeWebhookEvent::route('/create'),
            'view' => ViewStripeWebhookEvent::route('/{record}'),
            'edit' => EditStripeWebhookEvent::route('/{record}/edit'),
        ];
    }
}
