<?php

use App\Enums\MessageStatus;
use App\Jobs\DispatchApplicationEvent;
use App\Jobs\SendWhatsAppMessage;
use App\Messaging\WhatsAppWebhookProcessor;
use App\Models\ApplicationEventDelivery;
use App\Models\ConnectedApplication;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppWebhookEvent;
use Illuminate\Support\Facades\Queue;

function incomingEvent(string $type = 'text', array $content = ['body' => '1'], string $id = 'wamid.incoming.1'): WhatsAppWebhookEvent
{
    return WhatsAppWebhookEvent::factory()->create([
        'raw_payload' => [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Customer'], 'wa_id' => '12074097887']],
                        'messages' => [[
                            'from' => '12074097887',
                            'id' => $id,
                            'timestamp' => '1785450000',
                            'type' => $type,
                            $type => $content,
                        ]],
                    ],
                ]],
            ]],
        ],
    ]);
}

test('incoming text stores encrypted contact message window and routes selection', function () {
    Queue::fake();
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    app(WhatsAppWebhookProcessor::class)->process(incomingEvent());
    $message = WhatsAppMessage::firstOrFail();
    $conversation = $message->conversation;

    expect($message->message_type)->toBe('text')
        ->and($message->text_body_encrypted)->toBe('1')
        ->and($conversation->product_slug)->toBe('kirada')
        ->and($conversation->connected_application_id)->toBe($application->id)
        ->and($conversation->customer_service_window_expires_at->isFuture())->toBeTrue()
        ->and(DB::table('whatsapp_messages')->value('text_body_encrypted'))->not->toBe('1')
        ->and(DB::table('whatsapp_contacts')->value('phone_number_encrypted'))->not->toBe('12074097887');
});

test('unknown and media message types are stored without crashing', function (string $type, array $content, ?string $mediaId) {
    Queue::fake();
    app(WhatsAppWebhookProcessor::class)->process(incomingEvent($type, $content, 'wamid.'.$type));

    expect(WhatsAppMessage::first()->message_type)->toBe($type)
        ->and(WhatsAppMessage::first()->media_id)->toBe($mediaId);
})->with([
    'image' => ['image', ['id' => 'media-1', 'mime_type' => 'image/jpeg'], 'media-1'],
    'unknown' => ['future_type', ['anything' => true], null],
]);

test('interactive selections are parsed and route products', function () {
    Queue::fake();
    ConnectedApplication::factory()->create(['slug' => 'djib-payroll']);
    app(WhatsAppWebhookProcessor::class)->process(incomingEvent('interactive', ['button_reply' => ['id' => '2', 'title' => 'Djib Payroll']], 'wamid.interactive'));

    expect(WhatsAppMessage::first()->text_body_encrypted)->toBe('Djib Payroll')
        ->and(WhatsAppMessage::first()->conversation->product_slug)->toBe('djib-payroll');
});

test('duplicate meta message ids do not duplicate normalized messages', function () {
    Queue::fake();
    $event = incomingEvent();
    app(WhatsAppWebhookProcessor::class)->process($event);
    app(WhatsAppWebhookProcessor::class)->process($event);

    expect(WhatsAppMessage::count())->toBe(1)->and(WhatsAppContact::count())->toBe(1);
});

test('automatic response is not queued when autoreply is disabled', function () {
    Queue::fake();
    config()->set('services.meta_whatsapp.autoreply_enabled', false);

    app(WhatsAppWebhookProcessor::class)->process(incomingEvent('text', ['body' => 'invalid selection'], 'wamid.no-autoreply'));

    Queue::assertNotPushed(SendWhatsAppMessage::class);
});

test('menu command clears an existing route', function () {
    Queue::fake();
    ConnectedApplication::factory()->create(['slug' => 'kirada']);
    app(WhatsAppWebhookProcessor::class)->process(incomingEvent('text', ['body' => '1'], 'wamid.route'));
    app(WhatsAppWebhookProcessor::class)->process(incomingEvent('text', ['body' => 'menu'], 'wamid.menu'));
    $conversation = WhatsAppMessage::latest()->first()->conversation;

    expect($conversation->product_slug)->toBeNull()
        ->and($conversation->connected_application_id)->toBeNull()
        ->and($conversation->state->value)->toBe('awaiting_product_selection');
});

test('status progression is idempotent and never moves backward', function () {
    $message = WhatsAppMessage::factory()->create(['provider' => 'meta', 'provider_message_id' => 'wamid.status', 'meta_message_id' => 'wamid.status', 'direction' => 'outbound', 'status' => 'sent']);

    foreach (['delivered', 'sent', 'read', 'read'] as $status) {
        $event = WhatsAppWebhookEvent::factory()->create([
            'payload_hash' => fake()->sha256(),
            'raw_payload' => ['entry' => [['changes' => [['value' => ['statuses' => [['id' => 'wamid.status', 'status' => $status]]]]]]]],
        ]);
        app(WhatsAppWebhookProcessor::class)->process($event);
    }

    expect($message->fresh()->status)->toBe(MessageStatus::Read)
        ->and($message->fresh()->delivered_at)->not->toBeNull()
        ->and($message->fresh()->read_at)->not->toBeNull();
});

test('failed status stores sanitized failure details', function () {
    $message = WhatsAppMessage::factory()->create(['provider' => 'meta', 'provider_message_id' => 'wamid.failed', 'meta_message_id' => 'wamid.failed', 'direction' => 'outbound', 'status' => 'sent']);
    $event = WhatsAppWebhookEvent::factory()->create([
        'raw_payload' => ['entry' => [['changes' => [['value' => ['statuses' => [[
            'id' => 'wamid.failed', 'status' => 'failed', 'errors' => [['code' => '131000', 'title' => str_repeat('x', 700)]],
        ]]]]]]]],
    ]);
    app(WhatsAppWebhookProcessor::class)->process($event);

    expect($message->fresh()->status)->toBe(MessageStatus::Failed)
        ->and($message->fresh()->failure_code)->toBe('131000')
        ->and(strlen($message->fresh()->failure_message))->toBeLessThanOrEqual(503);
});

test('outbound status changes queue signed application events', function () {
    Queue::fake();
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    $message = WhatsAppMessage::factory()->create([
        'connected_application_id' => $application->id,
        'provider' => 'meta',
        'provider_message_id' => 'wamid.application-status',
        'meta_message_id' => 'wamid.application-status',
        'direction' => 'outbound',
        'status' => 'accepted',
    ]);
    $event = WhatsAppWebhookEvent::factory()->create([
        'raw_payload' => ['entry' => [['changes' => [['value' => ['statuses' => [[
            'id' => 'wamid.application-status',
            'status' => 'delivered',
        ]]]]]]]],
    ]);

    app(WhatsAppWebhookProcessor::class)->process($event);

    $delivery = ApplicationEventDelivery::firstOrFail();
    expect($delivery->event_type)->toBe('whatsapp.message.status')
        ->and($delivery->whatsapp_message_id)->toBe($message->id)
        ->and(data_get($delivery->payload, 'data.message_id'))->toBe($message->id)
        ->and(data_get($delivery->payload, 'data.provider_message_id'))->toBe('wamid.application-status')
        ->and(data_get($delivery->payload, 'data.status'))->toBe('delivered')
        ->and(data_get($delivery->payload, 'data.occurred_at'))->not->toBeNull();
    Queue::assertPushedOn('application-events', DispatchApplicationEvent::class);
});
