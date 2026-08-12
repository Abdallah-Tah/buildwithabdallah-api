<?php

namespace App\Observers;

use App\Enums\MessageDirection;
use App\Jobs\DispatchApplicationEvent;
use App\Models\ApplicationEventDelivery;
use App\Models\WhatsAppMessage;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Str;

class WhatsAppMessageObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(WhatsAppMessage $whatsAppMessage): void
    {
        if (! $whatsAppMessage->wasChanged('status')
            || $whatsAppMessage->direction !== MessageDirection::Outbound
            || ! $whatsAppMessage->connected_application_id) {
            return;
        }

        $eventId = (string) Str::ulid();
        $occurredAt = match ($whatsAppMessage->status->value) {
            'sent' => $whatsAppMessage->sent_at,
            'delivered' => $whatsAppMessage->delivered_at,
            'read' => $whatsAppMessage->read_at,
            'failed' => $whatsAppMessage->failed_at,
            default => null,
        } ?? now();
        $delivery = ApplicationEventDelivery::create([
            'event_id' => $eventId,
            'connected_application_id' => $whatsAppMessage->connected_application_id,
            'whatsapp_message_id' => $whatsAppMessage->id,
            'event_type' => 'whatsapp.message.status',
            'payload' => [
                'id' => $eventId,
                'event_id' => $eventId,
                'type' => 'whatsapp.message.status',
                'event_type' => 'whatsapp.message.status',
                'occurred_at' => $occurredAt->toIso8601String(),
                'product' => $whatsAppMessage->connectedApplication?->slug,
                'data' => [
                    'message_id' => $whatsAppMessage->id,
                    'provider' => $whatsAppMessage->provider,
                    'provider_message_id' => $whatsAppMessage->provider_message_id ?? $whatsAppMessage->meta_message_id,
                    'correlation_id' => $whatsAppMessage->correlation_id,
                    'idempotency_key' => $whatsAppMessage->idempotency_key,
                    'status' => $whatsAppMessage->status->value,
                    'occurred_at' => $occurredAt->toIso8601String(),
                    'error' => $whatsAppMessage->status->value === 'failed' ? [
                        'code' => $whatsAppMessage->failure_code,
                        'message' => $whatsAppMessage->failure_message,
                    ] : null,
                ],
            ],
        ]);

        DispatchApplicationEvent::dispatch($delivery)->onQueue('application-events');
    }
}
