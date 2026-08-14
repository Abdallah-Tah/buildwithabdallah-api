<?php

namespace App\Filament\Resources\ConnectedApplications\Pages;

use App\Filament\Resources\ConnectedApplications\ConnectedApplicationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewConnectedApplication extends ViewRecord
{
    protected static string $resource = ConnectedApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
