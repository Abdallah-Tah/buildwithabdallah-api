<?php

namespace App\Filament\Resources\ConnectedApplications;

use App\Filament\Resources\ConnectedApplications\Pages\CreateConnectedApplication;
use App\Filament\Resources\ConnectedApplications\Pages\EditConnectedApplication;
use App\Filament\Resources\ConnectedApplications\Pages\ListConnectedApplications;
use App\Filament\Resources\ConnectedApplications\Pages\ViewConnectedApplication;
use App\Filament\Resources\ConnectedApplications\Schemas\ConnectedApplicationForm;
use App\Filament\Resources\ConnectedApplications\Schemas\ConnectedApplicationInfolist;
use App\Filament\Resources\ConnectedApplications\Tables\ConnectedApplicationsTable;
use App\Models\ConnectedApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConnectedApplicationResource extends Resource
{
    protected static ?string $model = ConnectedApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Applications';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string
    {
        return 'Platform';
    }

    public static function form(Schema $schema): Schema
    {
        return ConnectedApplicationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ConnectedApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConnectedApplicationsTable::configure($table);
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
            'index' => ListConnectedApplications::route('/'),
            'create' => CreateConnectedApplication::route('/create'),
            'view' => ViewConnectedApplication::route('/{record}'),
            'edit' => EditConnectedApplication::route('/{record}/edit'),
        ];
    }
}
