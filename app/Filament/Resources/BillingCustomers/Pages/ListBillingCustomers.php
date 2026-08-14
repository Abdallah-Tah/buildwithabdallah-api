<?php

namespace App\Filament\Resources\BillingCustomers\Pages;

use App\Filament\Resources\BillingCustomers\BillingCustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillingCustomers extends ListRecords
{
    protected static string $resource = BillingCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
