<?php

namespace App\Filament\Resources\StripeWebhookEvents\Pages;

use App\Filament\Resources\StripeWebhookEvents\StripeWebhookEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditStripeWebhookEvent extends EditRecord
{
    protected static string $resource = StripeWebhookEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
