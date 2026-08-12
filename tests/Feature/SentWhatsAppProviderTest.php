<?php

use App\Jobs\SendWhatsAppMessage;
use App\Messaging\SentWhatsAppProvider;
use App\Messaging\WhatsAppProviderManager;
use App\Models\ConnectedApplication;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config()->set('services.whatsapp.provider', 'sent');
    config()->set('services.sent_dm.enabled', true);
    config()->set('services.whatsapp.live_send_enabled', true);
    config()->set('services.sent_dm.api_key', 'sent-test-key');
    config()->set('services.sent_dm.base_url', 'https://api.sent.dm');
    config()->set('services.sent_dm.sandbox', false);
});

test('sent adapter translates a Kirada template and preserves provider tracking', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.sent.dm/v3/messages' => Http::response([
            'success' => true,
            'data' => [
                'status' => 'QUEUED',
                'recipients' => [[
                    'message_id' => 'sent-message-id',
                    'to' => '+25377852037',
                    'channel' => 'whatsapp',
                ]],
            ],
            'error' => null,
            'meta' => ['request_id' => 'sent-request-id'],
        ], 202),
        'example.test/api/internal/bwa/whatsapp/events' => Http::response(status: 202),
    ]);
    config()->set('services.sent_dm.template_map', [
        'kirada_tenant_invitation' => [
            'fr' => [
                'id' => 'sent-template-id',
                'parameters' => ['tenant_name', 'landlord_name', 'accept_url', 'expires_at'],
            ],
        ],
    ]);
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    $contact = WhatsAppContact::factory()->create(['phone_number_encrypted' => '25377852037']);
    $conversation = WhatsAppConversation::factory()->create([
        'whatsapp_contact_id' => $contact->id,
        'connected_application_id' => $application->id,
    ]);
    $message = WhatsAppMessage::factory()->create([
        'whatsapp_contact_id' => $contact->id,
        'whatsapp_conversation_id' => $conversation->id,
        'connected_application_id' => $application->id,
        'idempotency_key' => 'kirada-invitation-1',
        'direction' => 'outbound',
        'message_type' => 'template',
        'template_name' => 'kirada_tenant_invitation',
        'template_language' => 'fr',
        'status' => 'queued',
        'request_payload' => [
            'template' => [
                'components' => [[
                    'type' => 'body',
                    'parameters' => array_map(
                        fn (string $value): array => ['type' => 'text', 'text' => $value],
                        ['Adna', 'Abdallah', 'https://kirada.net/invite/example', '14/08/2026'],
                    ),
                ]],
            ],
        ],
    ]);

    (new SendWhatsAppMessage($message))->handle(app(WhatsAppProviderManager::class));

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.sent.dm/v3/messages'
        && $request->hasHeader('x-api-key', 'sent-test-key')
        && $request->hasHeader('Idempotency-Key', 'kirada-invitation-1')
        && $request['to'] === ['+25377852037']
        && $request['channel'] === ['whatsapp']
        && $request['template'] === [
            'id' => 'sent-template-id',
            'parameters' => [
                'tenant_name' => 'Adna',
                'landlord_name' => 'Abdallah',
                'accept_url' => 'https://kirada.net/invite/example',
                'expires_at' => '14/08/2026',
            ],
        ]);
    expect($message->fresh())
        ->provider->toBe('sent')
        ->provider_message_id->toBe('sent-message-id')
        ->meta_message_id->toBeNull()
        ->and($message->fresh()->status->value)->toBe('accepted');
});

test('provider manager keeps Meta available without using it when Sent is selected', function () {
    expect(app(WhatsAppProviderManager::class)->active())
        ->toBeInstanceOf(SentWhatsAppProvider::class)
        ->and(app(WhatsAppProviderManager::class)->driver('meta')->name())->toBe('meta');
});

test('sent translates a private PDF document into a temporary signed parameter URL', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.sent.dm/v3/messages' => Http::response([
            'success' => true,
            'data' => [
                'status' => 'QUEUED',
                'recipients' => [[
                    'message_id' => 'sent-invoice-message-id',
                    'channel' => 'whatsapp',
                ]],
            ],
            'meta' => ['request_id' => 'sent-invoice-request-id'],
        ], 202),
    ]);
    Storage::fake('local');
    config()->set('services.sent_dm.template_map', [
        'kirada_rent_invoice' => [
            'fr' => [
                'id' => 'sent-invoice-template-id',
                'parameters' => ['document', 'invoice_number'],
            ],
        ],
    ]);
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    $contact = WhatsAppContact::factory()->create(['phone_number_encrypted' => '25377852037']);
    $conversation = WhatsAppConversation::factory()->create([
        'whatsapp_contact_id' => $contact->id,
        'connected_application_id' => $application->id,
    ]);
    $message = WhatsAppMessage::factory()->create([
        'whatsapp_contact_id' => $contact->id,
        'whatsapp_conversation_id' => $conversation->id,
        'connected_application_id' => $application->id,
        'provider' => 'sent',
        'direction' => 'outbound',
        'message_type' => 'template',
        'template_name' => 'kirada_rent_invoice',
        'template_language' => 'fr',
        'status' => 'queued',
        'request_payload' => [
            'template' => [
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [[
                            'type' => 'document',
                            'document' => [
                                'filename' => 'invoice-001.pdf',
                                'mime_type' => 'application/pdf',
                                'content_base64' => base64_encode('%PDF-1.4 test invoice'),
                            ],
                        ]],
                    ],
                    [
                        'type' => 'body',
                        'parameters' => [['type' => 'text', 'text' => 'INV-001']],
                    ],
                ],
            ],
        ],
    ]);

    app(SentWhatsAppProvider::class)->send($message, '25377852037');

    Http::assertSent(function ($request) use ($message): bool {
        $documentUrl = data_get($request->data(), 'template.parameters.document');

        return is_string($documentUrl)
            && str_contains($documentUrl, '/media/sent/whatsapp/'.$message->id)
            && str_contains($documentUrl, 'signature=')
            && data_get($request->data(), 'template.parameters.invoice_number') === 'INV-001';
    });
    Storage::disk('local')->assertExists('sent-whatsapp-documents/'.$message->id.'.pdf');
});
