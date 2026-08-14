<?php

namespace App\Filament\Resources\StripeWebhookEvents\Pages;

use App\Filament\Resources\StripeWebhookEvents\StripeWebhookEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStripeWebhookEvent extends CreateRecord
{
    protected static string $resource = StripeWebhookEventResource::class;
}
