<?php

namespace App\Filament\Resources\StripeWebhookEvents\Pages;

use App\Filament\Resources\StripeWebhookEvents\StripeWebhookEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStripeWebhookEvents extends ListRecords
{
    protected static string $resource = StripeWebhookEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
