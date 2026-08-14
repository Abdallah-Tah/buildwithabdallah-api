<?php

namespace App\Filament\Resources\BillingCustomers;

use App\Filament\Resources\BillingCustomers\Pages\CreateBillingCustomer;
use App\Filament\Resources\BillingCustomers\Pages\EditBillingCustomer;
use App\Filament\Resources\BillingCustomers\Pages\ListBillingCustomers;
use App\Filament\Resources\BillingCustomers\Pages\ViewBillingCustomer;
use App\Filament\Resources\BillingCustomers\Schemas\BillingCustomerForm;
use App\Filament\Resources\BillingCustomers\Schemas\BillingCustomerInfolist;
use App\Filament\Resources\BillingCustomers\Tables\BillingCustomersTable;
use App\Models\BillingCustomer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BillingCustomerResource extends Resource
{
    protected static ?string $model = BillingCustomer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $navigationLabel = 'Billing customers';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string
    {
        return 'Billing';
    }

    public static function form(Schema $schema): Schema
    {
        return BillingCustomerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BillingCustomerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingCustomersTable::configure($table);
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
            'index' => ListBillingCustomers::route('/'),
            'create' => CreateBillingCustomer::route('/create'),
            'view' => ViewBillingCustomer::route('/{record}'),
            'edit' => EditBillingCustomer::route('/{record}/edit'),
        ];
    }
}
