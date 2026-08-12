<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use App\Jobs\SendWhatsAppMessage;
use App\Models\ConnectedApplication;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;

class CreateOutboundWhatsAppMessage
{
    /** @param array<string, mixed> $data */
    public function handle(ConnectedApplication $application, array $data): WhatsAppMessage
    {
        if ($data['product'] !== $application->slug && ! (bool) Arr::get($application->metadata, 'may_send_for_other_products', false)) {
            $this->fail('PRODUCT_NOT_AUTHORIZED', 'The product does not match the authenticated application.', 403);
        }

        $requestHash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        $existing = WhatsAppMessage::query()->whereBelongsTo($application)->where('idempotency_key', $data['idempotency_key'])->first();

        if ($existing) {
            if (! hash_equals((string) $existing->request_hash, $requestHash)) {
                $this->fail('IDEMPOTENCY_CONFLICT', 'The idempotency key was already used with different content.', 409);
            }

            return $existing;
        }

        $recipient = preg_replace('/\D+/', '', $data['recipient']) ?? '';
        $contact = WhatsAppContact::query()->where('phone_number_hash', hash('sha256', $recipient))->first();
        $conversation = $contact
            ? WhatsAppConversation::query()->whereBelongsTo($contact, 'contact')->where('product_slug', $data['product'])->latest()->first()
            : null;

        if ($data['type'] === 'text' && (! $conversation?->customer_service_window_expires_at || $conversation->customer_service_window_expires_at->isPast())) {
            $this->fail('CUSTOMER_SERVICE_WINDOW_EXPIRED', 'An approved WhatsApp template is required outside the customer-service window.', 422);
        }

        $contact ??= WhatsAppContact::create([
            'wa_id_hash' => hash('sha256', $recipient),
            'wa_id_encrypted' => $recipient,
            'phone_number_hash' => hash('sha256', $recipient),
            'phone_number_encrypted' => $recipient,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $conversation ??= WhatsAppConversation::create([
            'whatsapp_contact_id' => $contact->id,
            'connected_application_id' => $application->id,
            'product_slug' => $data['product'],
            'state' => 'active',
            'routed_at' => now(),
        ]);

        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'whatsapp_contact_id' => $contact->id,
            'connected_application_id' => $application->id,
            'provider' => (string) config('services.whatsapp.provider'),
            'correlation_id' => $data['correlation_id'] ?? null,
            'idempotency_key' => $data['idempotency_key'],
            'request_hash' => $requestHash,
            'direction' => MessageDirection::Outbound,
            'message_type' => $data['type'],
            'status' => MessageStatus::Queued,
            'text_body_encrypted' => $data['body'] ?? null,
            'template_name' => Arr::get($data, 'template.name'),
            'template_language' => Arr::get($data, 'template.language'),
            'request_payload' => $data['type'] === 'template' ? ['template' => $data['template']] : null,
        ]);

        $conversation->update(['last_outgoing_message_at' => now()]);
        SendWhatsAppMessage::dispatch($message)->onQueue('whatsapp-outbound');

        return $message;
    }

    private function fail(string $code, string $message, int $status): never
    {
        throw new HttpResponseException(response()->json(['error' => ['code' => $code, 'message' => $message]], $status));
    }
}
