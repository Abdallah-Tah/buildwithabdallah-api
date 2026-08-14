<?php

namespace App\Filament\Resources\WhatsAppConversations\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhatsAppConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('whatsapp_contact_id')
                    ->searchable(),
                TextColumn::make('connectedApplication.name')
                    ->searchable(),
                TextColumn::make('product_slug')
                    ->searchable(),
                TextColumn::make('state')
                    ->badge()
                    ->searchable(),
                TextColumn::make('customer_service_window_started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('customer_service_window_expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_incoming_message_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_outgoing_message_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('routed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('closed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('connected_application_id')
                    ->label('Application')
                    ->relationship('connectedApplication', 'name'),
                SelectFilter::make('state')->options([
                    'new' => 'New',
                    'awaiting_product_selection' => 'Awaiting product',
                    'active' => 'Active',
                    'closed' => 'Closed',
                    'blocked' => 'Blocked',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->poll('15s');
    }
}
