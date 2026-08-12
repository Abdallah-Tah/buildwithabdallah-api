<?php

namespace App\Messaging;

use App\Enums\ConversationState;
use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use App\Jobs\DispatchApplicationEvent;
use App\Jobs\SendWhatsAppMessage;
use App\Models\ApplicationEventDelivery;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppWebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SentWhatsAppWebhookProcessor
{
    public function __construct(
        private ConversationRouter $router,
        private SentWhatsAppMedia $media,
    ) {}

    public function process(WhatsAppWebhookEvent $event): void
    {
        if (data_get($event->raw_payload, 'field') !== 'message') {
            return;
        }

        $eventType = data_get($event->raw_payload, 'event', data_get($event->raw_payload, 'sub_type'));

        if ($eventType === 'message.received') {
            $this->processInbound($event->raw_payload);

            return;
        }

        $this->processStatus($event->raw_payload);
    }

    /** @param array<string, mixed> $event */
    private function processStatus(array $event): void
    {
        $providerMessageId = data_get($event, 'payload.message_id');

        if (! is_string($providerMessageId) || $providerMessageId === '') {
            return;
        }

        $message = WhatsAppMessage::query()
            ->where('provider', 'sent')
            ->where('provider_message_id', $providerMessageId)
            ->first();
        $providerStatus = strtoupper((string) data_get($event, 'payload.message_status'));
        $status = match ($providerStatus) {
            'QUEUED', 'ROUTED', 'SCHEDULED' => MessageStatus::Accepted,
            'SENT' => MessageStatus::Sent,
            'DELIVERED' => MessageStatus::Delivered,
            'READ' => MessageStatus::Read,
            'FAILED', 'FILTERED', 'BLOCKED' => MessageStatus::Failed,
            default => null,
        };

        if (! $message || ! $status || ($status !== MessageStatus::Failed && $status->rank() < $message->status->rank())) {
            return;
        }

        $attributes = ['status' => $status];
        $timestampField = match ($status) {
            MessageStatus::Sent => 'sent_at',
            MessageStatus::Delivered => 'delivered_at',
            MessageStatus::Read => 'read_at',
            MessageStatus::Failed => 'failed_at',
            default => null,
        };

        if ($timestampField) {
            $attributes[$timestampField] = data_get($event, 'timestamp') ?: now();
        }

        if ($status === MessageStatus::Failed) {
            $attributes['failure_code'] = 'SENT_'.$providerStatus;
            $attributes['failure_message'] = 'Sent marked the WhatsApp message as '.strtolower($providerStatus).'.';
        }

        $message->update($attributes);

        if (in_array($status, [MessageStatus::Delivered, MessageStatus::Read, MessageStatus::Failed], true)) {
            $this->media->delete($message);
        }

        Log::info('whatsapp.status.updated', [
            'internal_message_id' => $message->id,
            'provider' => 'sent',
            'status' => $status->value,
        ]);
    }

    /** @param array<string, mixed> $event */
    private function processInbound(array $event): void
    {
        $providerMessageId = data_get($event, 'payload.message_id');

        if (! is_string($providerMessageId) || $providerMessageId === ''
            || WhatsAppMessage::query()->where('provider', 'sent')->where('provider_message_id', $providerMessageId)->exists()) {
            return;
        }

        DB::transaction(function () use ($event, $providerMessageId): void {
            $phone = (string) data_get($event, 'payload.inbound_number', data_get($event, 'payload.from'));
            $normalized = preg_replace('/\D+/', '', $phone) ?? '';
            $now = now();
            $contact = WhatsAppContact::query()->firstOrCreate(
                ['phone_number_hash' => hash('sha256', $normalized)],
                [
                    'wa_id_hash' => hash('sha256', $normalized),
                    'wa_id_encrypted' => $normalized,
                    'phone_number_encrypted' => $normalized,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                ],
            );
            $contact->update(['last_seen_at' => $now]);

            $conversation = WhatsAppConversation::query()
                ->whereBelongsTo($contact, 'contact')
                ->whereNotIn('state', [ConversationState::Closed->value, ConversationState::Blocked->value])
                ->latest()
                ->first();

            if (! $conversation) {
                $conversation = WhatsAppConversation::create([
                    'whatsapp_contact_id' => $contact->id,
                    'state' => ConversationState::AwaitingProductSelection,
                ]);
            }

            $windowHours = (int) config('services.meta_whatsapp.customer_service_window_hours', 24);
            $conversation->update([
                'customer_service_window_started_at' => $now,
                'customer_service_window_expires_at' => $now->copy()->addHours($windowHours),
                'last_incoming_message_at' => $now,
            ]);

            $text = data_get($event, 'payload.text');
            $message = WhatsAppMessage::create([
                'whatsapp_conversation_id' => $conversation->id,
                'whatsapp_contact_id' => $contact->id,
                'connected_application_id' => $conversation->connected_application_id,
                'provider' => 'sent',
                'provider_message_id' => $providerMessageId,
                'direction' => MessageDirection::Inbound,
                'message_type' => 'text',
                'status' => MessageStatus::Received,
                'text_body_encrypted' => is_string($text) ? $text : null,
                'request_payload' => array_filter([
                    'channel' => data_get($event, 'payload.channel'),
                    'received_at' => data_get($event, 'payload.received_at'),
                ]),
            ]);

            $this->router->route($conversation, is_string($text) ? $text : null);
            $message->update(['connected_application_id' => $conversation->fresh()->connected_application_id]);
            $this->queueAutomaticReply($conversation->fresh());

            if ($message->connected_application_id) {
                $eventId = (string) Str::ulid();
                $delivery = ApplicationEventDelivery::create([
                    'event_id' => $eventId,
                    'connected_application_id' => $message->connected_application_id,
                    'whatsapp_message_id' => $message->id,
                    'event_type' => 'whatsapp.message.received',
                    'payload' => [
                        'id' => $eventId,
                        'event_id' => $eventId,
                        'type' => 'whatsapp.message.received',
                        'event_type' => 'whatsapp.message.received',
                        'occurred_at' => now()->toIso8601String(),
                        'product' => $message->conversation?->product_slug,
                        'conversation_id' => $message->whatsapp_conversation_id,
                        'data' => [
                            'message_id' => $message->id,
                            'provider' => 'sent',
                            'provider_message_id' => $providerMessageId,
                            'contact_id' => $message->whatsapp_contact_id,
                            'message_type' => 'text',
                            'text' => $message->text_body_encrypted,
                        ],
                    ],
                ]);
                DispatchApplicationEvent::dispatch($delivery)->onQueue('application-events');
            }

            Log::info('whatsapp.message.stored', [
                'internal_message_id' => $message->id,
                'provider' => 'sent',
                'conversation_id' => $conversation->id,
            ]);
        }, attempts: 3);
    }

    private function queueAutomaticReply(WhatsAppConversation $conversation): void
    {
        if (! config('services.meta_whatsapp.autoreply_enabled')) {
            return;
        }

        $product = $conversation->product_slug ? config('bwa_products.products.'.$conversation->product_slug) : null;
        $body = $conversation->state === ConversationState::Active && is_array($product)
            ? $product['confirmation']
            : config('bwa_products.menu');

        $reply = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'whatsapp_contact_id' => $conversation->whatsapp_contact_id,
            'connected_application_id' => $conversation->connected_application_id,
            'provider' => (string) config('services.whatsapp.provider'),
            'direction' => MessageDirection::Outbound,
            'message_type' => 'text',
            'status' => MessageStatus::Queued,
            'text_body_encrypted' => $body,
            'correlation_id' => 'autoreply-'.Str::ulid(),
        ]);

        SendWhatsAppMessage::dispatch($reply)->onQueue('whatsapp-outbound');
    }
}
