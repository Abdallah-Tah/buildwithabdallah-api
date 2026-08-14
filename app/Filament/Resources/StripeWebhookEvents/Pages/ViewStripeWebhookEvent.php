<?php

namespace App\Filament\Resources\StripeWebhookEvents\Pages;

use App\Filament\Resources\StripeWebhookEvents\StripeWebhookEventResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStripeWebhookEvent extends ViewRecord
{
    protected static string $resource = StripeWebhookEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
