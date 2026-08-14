<?php

namespace App\Filament\Resources\ApplicationEventDeliveries\Pages;

use App\Filament\Resources\ApplicationEventDeliveries\ApplicationEventDeliveryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewApplicationEventDelivery extends ViewRecord
{
    protected static string $resource = ApplicationEventDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
