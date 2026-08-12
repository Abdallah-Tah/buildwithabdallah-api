<?php

use App\Jobs\ProcessWhatsAppWebhook;
use App\Messaging\SentWhatsAppWebhookProcessor;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppWebhookEvent;
use Illuminate\Support\Facades\Queue;

function sentWebhookHeaders(string $body, string $secret, ?string $timestamp = null): array
{
    $webhookId = '7ba7b820-9dad-11d1-80b4-00c04fd430c8';
    $timestamp ??= (string) now()->timestamp;
    $key = base64_decode(str_replace('whsec_', '', $secret), true);
    $signature = 'v1,'.base64_encode(hash_hmac('sha256', $webhookId.'.'.$timestamp.'.'.$body, $key, true));

    return [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WEBHOOK_ID' => $webhookId,
        'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
        'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
    ];
}

beforeEach(function (): void {
    config()->set('services.sent_dm.webhook_secret', 'whsec_'.base64_encode('sent-signing-secret'));
});

test('valid Sent signature persists and queues the event once', function () {
    Queue::fake();
    $body = json_encode([
        'field' => 'message',
        'event' => 'message.delivered',
        'timestamp' => now()->toIso8601String(),
        'payload' => ['message_id' => 'sent-message-id', 'message_status' => 'DELIVERED'],
    ], JSON_THROW_ON_ERROR);
    $headers = sentWebhookHeaders($body, (string) config('services.sent_dm.webhook_secret'));

    $this->call('POST', '/webhooks/sent/whatsapp', [], [], [], $headers, $body)
        ->assertSuccessful()
        ->assertJson(['received' => true]);
    $this->call('POST', '/webhooks/sent/whatsapp', [], [], [], $headers, $body)->assertSuccessful();

    expect(WhatsAppWebhookEvent::count())->toBe(1)
        ->and(WhatsAppWebhookEvent::first()->provider)->toBe('sent');
    Queue::assertPushed(ProcessWhatsAppWebhook::class, 1);
});

test('invalid or stale Sent signatures are rejected', function (array $headers) {
    $body = '{}';

    $this->call('POST', '/webhooks/sent/whatsapp', [], [], [], $headers, $body)->assertForbidden();
    expect(WhatsAppWebhookEvent::count())->toBe(0);
})->with([
    'missing headers' => [[]],
    'invalid signature' => [[
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WEBHOOK_ID' => 'webhook-id',
        'HTTP_X_WEBHOOK_TIMESTAMP' => '1786250000',
        'HTTP_X_WEBHOOK_SIGNATURE' => 'v1,invalid',
    ]],
]);

test('Sent delivery events update generic provider messages', function () {
    Queue::fake();
    $message = WhatsAppMessage::factory()->create([
        'provider' => 'sent',
        'provider_message_id' => 'sent-status-id',
        'direction' => 'outbound',
        'status' => 'accepted',
    ]);
    $event = WhatsAppWebhookEvent::factory()->create([
        'provider' => 'sent',
        'raw_payload' => [
            'field' => 'message',
            'event' => 'message.delivered',
            'timestamp' => now()->toIso8601String(),
            'payload' => [
                'message_id' => 'sent-status-id',
                'message_status' => 'DELIVERED',
                'channel' => 'whatsapp',
            ],
        ],
    ]);

    app(SentWhatsAppWebhookProcessor::class)->process($event);

    expect($message->fresh()->status->value)->toBe('delivered')
        ->and($message->fresh()->delivered_at)->not->toBeNull();
});
