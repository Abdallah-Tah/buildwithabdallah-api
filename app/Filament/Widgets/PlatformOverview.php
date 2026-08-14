<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ApplicationEventDeliveries\ApplicationEventDeliveryResource;
use App\Filament\Resources\BillingCustomers\BillingCustomerResource;
use App\Filament\Resources\ConnectedApplications\ConnectedApplicationResource;
use App\Filament\Resources\WhatsAppMessages\WhatsAppMessageResource;
use App\Models\ApplicationEventDelivery;
use App\Models\BillingCustomer;
use App\Models\ConnectedApplication;
use App\Models\StripeWebhookEvent;
use App\Models\WhatsAppMessage;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Central API status';

    protected ?string $description = 'Live operational signals across messaging and billing.';

    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $applications = ConnectedApplication::query()->count();
        $enabledApplications = ConnectedApplication::query()->where('enabled', true)->count();
        $messagesToday = WhatsAppMessage::query()->where('created_at', '>=', now()->startOfDay())->count();
        $failedMessages = WhatsAppMessage::query()->where('status', 'failed')->where('created_at', '>=', now()->subDay())->count();
        $pendingDeliveries = ApplicationEventDelivery::query()->whereIn('status', ['pending', 'failed'])->count();
        $failedStripeEvents = StripeWebhookEvent::query()->where('status', 'failed')->count();

        return [
            Stat::make('Connected applications', $enabledApplications.' / '.$applications)
                ->description($applications === $enabledApplications ? 'All applications enabled' : 'Review disabled applications')
                ->descriptionIcon($applications === $enabledApplications ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($applications === $enabledApplications ? 'success' : 'warning')
                ->icon('heroicon-o-squares-plus')
                ->url(ConnectedApplicationResource::getUrl()),
            Stat::make('Billing customers', BillingCustomer::query()->count())
                ->description($failedStripeEvents === 0 ? 'Stripe events healthy' : $failedStripeEvents.' failed Stripe event(s)')
                ->descriptionIcon($failedStripeEvents === 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($failedStripeEvents === 0 ? 'success' : 'danger')
                ->icon('heroicon-o-credit-card')
                ->url(BillingCustomerResource::getUrl()),
            Stat::make('WhatsApp messages today', $messagesToday)
                ->description($failedMessages === 0 ? 'No failures in the last 24 hours' : $failedMessages.' failed in the last 24 hours')
                ->descriptionIcon($failedMessages === 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($failedMessages === 0 ? 'success' : 'danger')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url(WhatsAppMessageResource::getUrl()),
            Stat::make('Deliveries needing attention', $pendingDeliveries)
                ->description($pendingDeliveries === 0 ? 'Application webhooks are clear' : 'Pending or failed callbacks')
                ->descriptionIcon($pendingDeliveries === 0 ? 'heroicon-m-check-circle' : 'heroicon-m-arrow-path')
                ->color($pendingDeliveries === 0 ? 'success' : 'warning')
                ->icon('heroicon-o-paper-airplane')
                ->url(ApplicationEventDeliveryResource::getUrl()),
        ];
    }
}
