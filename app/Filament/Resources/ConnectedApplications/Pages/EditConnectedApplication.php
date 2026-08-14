<?php

namespace App\Filament\Resources\ConnectedApplications\Pages;

use App\Filament\Resources\ConnectedApplications\ConnectedApplicationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditConnectedApplication extends EditRecord
{
    protected static string $resource = ConnectedApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
