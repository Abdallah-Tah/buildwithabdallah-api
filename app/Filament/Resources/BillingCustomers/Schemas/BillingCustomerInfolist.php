<?php

namespace App\Filament\Resources\BillingCustomers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BillingCustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Customer')->schema([
                TextEntry::make('connectedApplication.name')->label('Application'),
                TextEntry::make('external_customer_id')->label('Application customer ID')->copyable(),
                TextEntry::make('name')->placeholder('Not provided'),
                TextEntry::make('email')->copyable()->placeholder('Not provided'),
                TextEntry::make('stripe_customer_id')->label('Stripe customer')->copyable(),
                TextEntry::make('created_at')->dateTime(),
            ])->columns(2),
        ]);
    }
}
