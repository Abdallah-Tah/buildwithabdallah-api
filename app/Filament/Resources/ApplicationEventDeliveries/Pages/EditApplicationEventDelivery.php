<?php

namespace App\Filament\Resources\ApplicationEventDeliveries\Pages;

use App\Filament\Resources\ApplicationEventDeliveries\ApplicationEventDeliveryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditApplicationEventDelivery extends EditRecord
{
    protected static string $resource = ApplicationEventDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
