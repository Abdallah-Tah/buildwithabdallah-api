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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebhookProcessor
{
    public function __construct(private ConversationRouter $router) {}

    public function process(WhatsAppWebhookEvent $event): void
    {
        foreach (Arr::get($event->raw_payload, 'entry', []) as $entry) {
            foreach (Arr::get($entry, 'changes', []) as $change) {
                $value = Arr::get($change, 'value', []);

                foreach (Arr::get($value, 'messages', []) as $message) {
                    $this->processMessage($value, $message);
                }

                foreach (Arr::get($value, 'statuses', []) as $status) {
                    $this->processStatus($status);
                }
            }
        }
    }

    /** @param array<string, mixed> $value @param array<string, mixed> $payload */
    private function processMessage(array $value, array $payload): void
    {
        $metaMessageId = Arr::get($payload, 'id');

        if (! is_string($metaMessageId) || WhatsAppMessage::query()->where('meta_message_id', $metaMessageId)->exists()) {
            return;
        }

        DB::transaction(function () use ($value, $payload, $metaMessageId): void {
            $waId = (string) Arr::get($payload, 'from');
            $normalized = preg_replace('/\D+/', '', $waId) ?? '';
            $displayName = Arr::get($value, 'contacts.0.profile.name');
            $now = now();
            $contact = WhatsAppContact::query()->firstOrCreate(
                ['wa_id_hash' => hash('sha256', $waId)],
                [
                    'wa_id_encrypted' => $waId,
                    'phone_number_hash' => hash('sha256', $normalized),
                    'phone_number_encrypted' => $normalized,
                    'display_name_encrypted' => is_string($displayName) ? $displayName : null,
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

            $windowHours = config('services.meta_whatsapp.customer_service_window_hours', 24);
            $conversation->update([
                'customer_service_window_started_at' => $now,
                'customer_service_window_expires_at' => $now->copy()->addHours($windowHours),
                'last_incoming_message_at' => $now,
            ]);

            $type = (string) Arr::get($payload, 'type', 'unknown');
            $text = $this->extractText($payload, $type);
            $media = Arr::get($payload, $type, []);
            $message = WhatsAppMessage::create([
                'whatsapp_conversation_id' => $conversation->id,
                'whatsapp_contact_id' => $contact->id,
                'connected_application_id' => $conversation->connected_application_id,
                'meta_message_id' => $metaMessageId,
                'direction' => MessageDirection::Inbound,
                'message_type' => $type,
                'status' => MessageStatus::Received,
                'text_body_encrypted' => $text,
                'media_id' => is_array($media) ? Arr::get($media, 'id') : null,
                'reply_to_meta_message_id' => Arr::get($payload, 'context.id'),
                'request_payload' => $this->safeMetadata($payload, $type),
            ]);

            $this->router->route($conversation, $text);
            $message->update(['connected_application_id' => $conversation->fresh()->connected_application_id]);
            $this->queueAutomaticReply($conversation->fresh(), $text);

            if ($message->connected_application_id) {
                $delivery = ApplicationEventDelivery::create([
                    'event_id' => (string) Str::ulid(),
                    'connected_application_id' => $message->connected_application_id,
                    'whatsapp_message_id' => $message->id,
                    'event_type' => 'whatsapp.message.received',
                    'payload' => $this->eventPayload($message->fresh(['conversation'])),
                ]);
                DispatchApplicationEvent::dispatch($delivery)->onQueue('application-events');
            }

            Log::info('whatsapp.message.stored', ['internal_message_id' => $message->id, 'conversation_id' => $conversation->id]);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $payload */
    private function processStatus(array $payload): void
    {
        $message = WhatsAppMessage::query()->where('meta_message_id', Arr::get($payload, 'id'))->first();
        $status = MessageStatus::tryFrom((string) Arr::get($payload, 'status'));

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
            $attributes[$timestampField] = now();
        }

        if ($status === MessageStatus::Failed) {
            $attributes['failure_code'] = Str::limit((string) Arr::get($payload, 'errors.0.code'), 100, '');
            $attributes['failure_message'] = Str::limit((string) Arr::get($payload, 'errors.0.title', 'Meta delivery failed.'), 500);
        }

        $message->update($attributes);
        Log::info('whatsapp.status.updated', ['internal_message_id' => $message->id, 'status' => $status->value]);
    }

    /** @param array<string, mixed> $payload */
    private function extractText(array $payload, string $type): ?string
    {
        return match ($type) {
            'text' => Arr::get($payload, 'text.body'),
            'button' => Arr::get($payload, 'button.text'),
            'interactive' => Arr::get($payload, 'interactive.button_reply.title') ?? Arr::get($payload, 'interactive.list_reply.title'),
            default => null,
        };
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function safeMetadata(array $payload, string $type): array
    {
        return array_filter([
            'timestamp' => Arr::get($payload, 'timestamp'),
            'mime_type' => Arr::get($payload, "{$type}.mime_type"),
            'filename' => Arr::get($payload, "{$type}.filename"),
            'caption' => Arr::get($payload, "{$type}.caption"),
            'interactive_id' => Arr::get($payload, 'interactive.button_reply.id') ?? Arr::get($payload, 'interactive.list_reply.id'),
        ], fn (mixed $value): bool => $value !== null);
    }

    /** @return array<string, mixed> */
    private function eventPayload(WhatsAppMessage $message): array
    {
        $payload = [
            'event_id' => null,
            'event_type' => 'whatsapp.message.received',
            'occurred_at' => now()->toIso8601String(),
            'product' => $message->conversation?->product_slug,
            'conversation_id' => $message->whatsapp_conversation_id,
            'data' => [
                'message_id' => $message->id,
                'meta_message_id' => $message->meta_message_id,
                'contact_id' => $message->whatsapp_contact_id,
                'message_type' => $message->message_type,
                'text' => $message->text_body_encrypted,
                'reply_to_message_id' => $message->reply_to_meta_message_id,
            ],
        ];

        if ((bool) Arr::get($message->connectedApplication?->metadata, 'include_phone_number', false)) {
            $payload['data']['phone_number'] = $message->contact?->phone_number_encrypted;
        }

        return $payload;
    }

    private function queueAutomaticReply(WhatsAppConversation $conversation, ?string $selection): void
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
            'direction' => MessageDirection::Outbound,
            'message_type' => 'text',
            'status' => MessageStatus::Queued,
            'text_body_encrypted' => $body,
            'correlation_id' => 'autoreply-'.Str::ulid(),
        ]);

        SendWhatsAppMessage::dispatch($reply)->onQueue('whatsapp-outbound');
    }
}
