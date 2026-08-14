<?php

namespace App\Filament\Resources\WhatsAppWebhookEvents\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhatsAppWebhookEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('payload_hash')
                    ->searchable(),
                TextColumn::make('object_type')
                    ->searchable(),
                TextColumn::make('event_type')
                    ->searchable(),
                TextColumn::make('received_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('processing_started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('failed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('attempt_count')
                    ->numeric()
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
            ])
            ->filters([
                SelectFilter::make('provider')->options(['meta' => 'Meta', 'sent' => 'Sent']),
                Filter::make('failed')->query(fn ($query) => $query->whereNotNull('failed_at')),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('received_at', 'desc')
            ->poll('15s');
    }
}
