<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ApplicationEventDeliveries\ApplicationEventDeliveryResource;
use App\Models\ApplicationEventDelivery;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentDeliveryFailures extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '15s';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent application delivery failures')
            ->description('Callbacks from the central API that require attention.')
            ->query(fn (): Builder => ApplicationEventDelivery::query()
                ->with('connectedApplication')
                ->where('status', 'failed')
                ->latest())
            ->columns([
                TextColumn::make('connectedApplication.name')->label('Application'),
                TextColumn::make('event_type')->label('Event')->badge(),
                TextColumn::make('attempt_count')->label('Attempts')->numeric(),
                TextColumn::make('response_status')->label('HTTP')->placeholder('No response'),
                TextColumn::make('last_error')->label('Last error')->limit(70)->tooltip(fn ($state) => $state),
                TextColumn::make('updated_at')->label('Last attempt')->since(),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (ApplicationEventDelivery $record): string => ApplicationEventDeliveryResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('No failed deliveries')
            ->emptyStateDescription('All application callbacks are currently healthy.')
            ->paginated([5, 10]);
    }
}
