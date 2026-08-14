<?php

namespace App\Filament\Resources\WhatsAppMessages\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhatsAppMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('whatsapp_conversation_id')
                    ->searchable(),
                TextColumn::make('whatsapp_contact_id')
                    ->searchable(),
                TextColumn::make('connectedApplication.name')
                    ->searchable(),
                TextColumn::make('meta_message_id')
                    ->searchable(),
                TextColumn::make('correlation_id')
                    ->searchable(),
                TextColumn::make('idempotency_key')
                    ->searchable(),
                TextColumn::make('request_hash')
                    ->searchable(),
                TextColumn::make('direction')
                    ->badge()
                    ->searchable(),
                TextColumn::make('message_type')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state->value ?? $state) {
                        'delivered', 'read', 'received' => 'success',
                        'failed' => 'danger',
                        'queued', 'accepted' => 'warning',
                        default => 'info',
                    })
                    ->searchable(),
                TextColumn::make('template_name')
                    ->searchable(),
                TextColumn::make('template_language')
                    ->searchable(),
                TextColumn::make('media_id')
                    ->searchable(),
                TextColumn::make('reply_to_meta_message_id')
                    ->searchable(),
                TextColumn::make('failure_code')
                    ->searchable(),
                TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('read_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('failed_at')
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
                TextColumn::make('provider')
                    ->searchable(),
                TextColumn::make('provider_message_id')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('connected_application_id')
                    ->label('Application')
                    ->relationship('connectedApplication', 'name'),
                SelectFilter::make('provider')->options(['meta' => 'Meta', 'sent' => 'Sent']),
                SelectFilter::make('direction')->options(['inbound' => 'Inbound', 'outbound' => 'Outbound']),
                SelectFilter::make('status')->options([
                    'queued' => 'Queued',
                    'accepted' => 'Accepted',
                    'sent' => 'Sent',
                    'delivered' => 'Delivered',
                    'read' => 'Read',
                    'received' => 'Received',
                    'failed' => 'Failed',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('15s');
    }
}
