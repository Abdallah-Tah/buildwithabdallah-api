<?php

namespace App\Filament\Resources\ApplicationEventDeliveries;

use App\Filament\Resources\ApplicationEventDeliveries\Pages\CreateApplicationEventDelivery;
use App\Filament\Resources\ApplicationEventDeliveries\Pages\EditApplicationEventDelivery;
use App\Filament\Resources\ApplicationEventDeliveries\Pages\ListApplicationEventDeliveries;
use App\Filament\Resources\ApplicationEventDeliveries\Pages\ViewApplicationEventDelivery;
use App\Filament\Resources\ApplicationEventDeliveries\Schemas\ApplicationEventDeliveryForm;
use App\Filament\Resources\ApplicationEventDeliveries\Schemas\ApplicationEventDeliveryInfolist;
use App\Filament\Resources\ApplicationEventDeliveries\Tables\ApplicationEventDeliveriesTable;
use App\Models\ApplicationEventDelivery;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ApplicationEventDeliveryResource extends Resource
{
    protected static ?string $model = ApplicationEventDelivery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $recordTitleAttribute = 'event_type';

    protected static ?string $navigationLabel = 'Event deliveries';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string
    {
        return 'Platform';
    }

    public static function form(Schema $schema): Schema
    {
        return ApplicationEventDeliveryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ApplicationEventDeliveryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApplicationEventDeliveriesTable::configure($table);
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
            'index' => ListApplicationEventDeliveries::route('/'),
            'create' => CreateApplicationEventDelivery::route('/create'),
            'view' => ViewApplicationEventDelivery::route('/{record}'),
            'edit' => EditApplicationEventDelivery::route('/{record}/edit'),
        ];
    }
}
