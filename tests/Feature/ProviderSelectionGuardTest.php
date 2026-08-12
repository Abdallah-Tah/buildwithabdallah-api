<?php

use App\Exceptions\MessagingConfigurationException;
use App\Jobs\SendWhatsAppMessage;
use App\Messaging\MetaWhatsAppProvider;
use App\Messaging\SentWhatsAppProvider;
use App\Messaging\WhatsAppProviderManager;
use App\Models\ConnectedApplication;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('services.whatsapp.live_send_enabled', true);
    config()->set('services.meta_whatsapp.live_send_enabled', true);
    config()->set('services.meta_whatsapp.access_token', 'test-token');
    config()->set('services.meta_whatsapp.graph_api_version', 'v26.0');
    config()->set('services.meta_whatsapp.phone_number_id', 'phone-number-id');
});

test('meta is the active provider by default', function () {
    config()->set('services.whatsapp.provider', 'meta');

    expect(app(WhatsAppProviderManager::class)->active())
        ->toBeInstanceOf(MetaWhatsAppProvider::class);
});

test('the sent provider is refused while it is disabled', function () {
    config()->set('services.whatsapp.provider', 'sent');
    config()->set('services.sent_dm.enabled', false);

    expect(fn () => app(WhatsAppProviderManager::class)->active())
        ->toThrow(MessagingConfigurationException::class, 'The Sent.dm provider is disabled.');
});

test('a stored sent provider on a message cannot bypass the guard', function () {
    config()->set('services.whatsapp.provider', 'meta');
    config()->set('services.sent_dm.enabled', false);

    expect(fn () => app(WhatsAppProviderManager::class)->driver('sent'))
        ->toThrow(MessagingConfigurationException::class);
});

test('the sent provider is still reachable when deliberately re-enabled', function () {
    config()->set('services.whatsapp.provider', 'sent');
    config()->set('services.sent_dm.enabled', true);

    expect(app(WhatsAppProviderManager::class)->active())
        ->toBeInstanceOf(SentWhatsAppProvider::class);
});

test('sending under the meta provider never calls sent.dm', function () {
    config()->set('services.whatsapp.provider', 'meta');
    config()->set('services.sent_dm.enabled', false);
    Http::preventStrayRequests();
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'messages' => [['id' => 'wamid.TEST']],
        ], 200),
        'example.test/api/internal/bwa/whatsapp/events' => Http::response(status: 202),
    ]);

    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    $contact = WhatsAppContact::factory()->create(['phone_number_encrypted' => '12074097887']);
    $conversation = WhatsAppConversation::factory()->create([
        'whatsapp_contact_id' => $contact->id,
        'connected_application_id' => $application->id,
    ]);
    $message = WhatsAppMessage::factory()->create([
        'whatsapp_contact_id' => $contact->id,
        'whatsapp_conversation_id' => $conversation->id,
        'connected_application_id' => $application->id,
        'direction' => 'outbound',
        'message_type' => 'text',
        'text_body_encrypted' => 'Kirada WhatsApp integration test.',
        'status' => 'queued',
    ]);

    (new SendWhatsAppMessage($message))->handle(app(WhatsAppProviderManager::class));

    Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com'));
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'sent.dm'));
    expect($message->fresh()->provider)->toBe('meta')
        ->and($message->fresh()->provider_message_id)->toBe('wamid.TEST');
});
