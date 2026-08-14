<?php

namespace App\Filament\Resources\ApplicationEventDeliveries\Tables;

use App\Jobs\DispatchApplicationEvent;
use App\Models\ApplicationEventDelivery;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ApplicationEventDeliveriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('event_id')
                    ->searchable(),
                TextColumn::make('connectedApplication.name')
                    ->searchable(),
                TextColumn::make('whatsapp_message_id')
                    ->searchable(),
                TextColumn::make('event_type')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'delivered' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    })
                    ->searchable(),
                TextColumn::make('attempt_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('response_status')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('delivered_at')
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
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'delivered' => 'Delivered',
                    'failed' => 'Failed',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (ApplicationEventDelivery $record): bool => $record->status === 'failed')
                    ->action(function (ApplicationEventDelivery $record): void {
                        $record->update([
                            'status' => 'pending',
                            'response_status' => null,
                            'last_error' => null,
                            'delivered_at' => null,
                        ]);

                        DispatchApplicationEvent::dispatch($record->fresh());

                        Notification::make()
                            ->title('Delivery queued for retry')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('15s');
    }
}
