<?php

use App\Jobs\ProcessWhatsAppWebhook;
use App\Models\WhatsAppWebhookEvent;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config()->set('services.meta_whatsapp.verify_token', 'safe-test-verify-token');
    config()->set('services.meta_whatsapp.app_secret', 'safe-test-app-secret');
});

test('correct verification returns the exact challenge', function () {
    $this->get('/webhooks/meta/whatsapp?hub_mode=subscribe&hub_verify_token=safe-test-verify-token&hub_challenge=123456')
        ->assertSuccessful()
        ->assertSeeText('123456');
});

test('invalid webhook verification is forbidden without leaking the token', function (array $query) {
    $response = $this->get('/webhooks/meta/whatsapp?'.http_build_query($query))->assertForbidden();

    expect($response->getContent())->not->toContain('safe-test-verify-token');
})->with([
    'wrong token' => [['hub_mode' => 'subscribe', 'hub_verify_token' => 'wrong', 'hub_challenge' => '1']],
    'wrong mode' => [['hub_mode' => 'other', 'hub_verify_token' => 'safe-test-verify-token', 'hub_challenge' => '1']],
    'missing values' => [[]],
]);

test('valid exact raw body signature persists and queues a webhook', function () {
    Queue::fake();
    $body = '{"object":"whatsapp_business_account","entry":[]}';
    $signature = 'sha256='.hash_hmac('sha256', $body, 'safe-test-app-secret');

    $this->call('POST', '/webhooks/meta/whatsapp', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => $signature,
    ], $body)->assertSuccessful()->assertSeeText('EVENT_RECEIVED');

    expect(WhatsAppWebhookEvent::count())->toBe(1);
    Queue::assertPushedOn('whatsapp-webhooks', ProcessWhatsAppWebhook::class);
});

test('missing invalid and body-mismatched signatures are rejected and not persisted', function (?string $signature) {
    $body = '{"object":"whatsapp_business_account","entry":[]}';
    $server = ['CONTENT_TYPE' => 'application/json'];

    if ($signature !== null) {
        $server['HTTP_X_HUB_SIGNATURE_256'] = $signature;
    }

    $this->call('POST', '/webhooks/meta/whatsapp', [], [], [], $server, $body)->assertForbidden();
    expect(WhatsAppWebhookEvent::count())->toBe(0);
})->with([
    'missing' => [null],
    'invalid' => ['sha256=invalid'],
    'different body' => ['sha256='.hash_hmac('sha256', '{"other":true}', 'safe-test-app-secret')],
]);

test('duplicate payload is persisted and queued only once', function () {
    Queue::fake();
    $body = '{"object":"whatsapp_business_account","entry":[]}';
    $server = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'safe-test-app-secret'),
    ];

    $this->call('POST', '/webhooks/meta/whatsapp', [], [], [], $server, $body)->assertSuccessful();
    $this->call('POST', '/webhooks/meta/whatsapp', [], [], [], $server, $body)->assertSuccessful();

    expect(WhatsAppWebhookEvent::count())->toBe(1);
    Queue::assertPushed(ProcessWhatsAppWebhook::class, 1);
});

test('valid signature with invalid json is not persisted', function () {
    $body = '{invalid';

    $this->call('POST', '/webhooks/meta/whatsapp', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'safe-test-app-secret'),
    ], $body)->assertBadRequest();

    expect(WhatsAppWebhookEvent::count())->toBe(0);
});
