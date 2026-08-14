<?php

namespace App\Filament\Resources\ConnectedApplications\Pages;

use App\Filament\Resources\ConnectedApplications\ConnectedApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConnectedApplications extends ListRecords
{
    protected static string $resource = ConnectedApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
