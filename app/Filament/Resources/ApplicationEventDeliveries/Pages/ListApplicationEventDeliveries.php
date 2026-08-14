<?php

namespace App\Filament\Resources\ApplicationEventDeliveries\Pages;

use App\Filament\Resources\ApplicationEventDeliveries\ApplicationEventDeliveryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApplicationEventDeliveries extends ListRecords
{
    protected static string $resource = ApplicationEventDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
