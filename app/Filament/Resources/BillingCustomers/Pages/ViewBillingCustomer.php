<?php

namespace App\Filament\Resources\BillingCustomers\Pages;

use App\Filament\Resources\BillingCustomers\BillingCustomerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBillingCustomer extends ViewRecord
{
    protected static string $resource = BillingCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
