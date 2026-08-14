<?php

namespace App\Filament\Resources\BillingCustomers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BillingCustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('connected_application_id')
                    ->relationship('connectedApplication', 'name')
                    ->required(),
                TextInput::make('external_customer_id')
                    ->required(),
                TextInput::make('stripe_customer_id')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('name'),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
