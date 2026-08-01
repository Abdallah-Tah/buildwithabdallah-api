<?php

use App\Models\ApplicationRequestNonce;
use App\Models\ConnectedApplication;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

function internalTemplateBody(): string
{
    return json_encode([
        'recipient' => '+12074097887',
        'type' => 'template',
        'product' => 'kirada',
        'template' => ['name' => 'rent_reminder', 'language' => 'en_US', 'components' => []],
        'idempotency_key' => 'request-1',
    ], JSON_THROW_ON_ERROR);
}

test('valid application signature authenticates and stores a nonce', function () {
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    $body = internalTemplateBody();

    $this->call('POST', '/api/v1/whatsapp/messages', [], [], [], signedApplicationHeaders($application, 'POST', '/api/v1/whatsapp/messages', $body), $body)
        ->assertAccepted();

    expect(ApplicationRequestNonce::count())->toBe(1);
});

test('invalid unknown expired and modified application requests fail', function (string $case) {
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    $body = internalTemplateBody();
    $headers = signedApplicationHeaders($application, 'POST', '/api/v1/whatsapp/messages', $body, timestamp: $case === 'expired' ? now()->subMinutes(10)->timestamp : null);

    if ($case === 'invalid') {
        $headers['HTTP_X_BWA_SIGNATURE'] = 'sha256=invalid';
    } elseif ($case === 'unknown') {
        $headers['HTTP_X_BWA_APP'] = 'unknown';
    } elseif ($case === 'modified') {
        $body = str_replace('request-1', 'request-2', $body);
    }

    $this->call('POST', '/api/v1/whatsapp/messages', [], [], [], $headers, $body)->assertUnauthorized();
    expect(ApplicationRequestNonce::count())->toBe(0);
})->with(['invalid', 'unknown', 'expired', 'modified']);

test('disabled applications are forbidden', function () {
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada', 'enabled' => false]);
    $body = internalTemplateBody();

    $this->call('POST', '/api/v1/whatsapp/messages', [], [], [], signedApplicationHeaders($application, 'POST', '/api/v1/whatsapp/messages', $body), $body)
        ->assertForbidden();
});

test('replayed request ids are rejected', function () {
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    $body = internalTemplateBody();
    $headers = signedApplicationHeaders($application, 'POST', '/api/v1/whatsapp/messages', $body, 'fixed-request-id');

    $this->call('POST', '/api/v1/whatsapp/messages', [], [], [], $headers, $body)->assertAccepted();
    $this->call('POST', '/api/v1/whatsapp/messages', [], [], [], $headers, $body)->assertConflict();
});

test('application secrets are encrypted at rest and hidden from serialization', function () {
    $application = ConnectedApplication::factory()->create([
        'request_signing_secret' => 'request-plain-value',
        'event_signing_secret' => 'event-plain-value',
    ]);
    $raw = DB::table($application->getTable())->where('id', $application->id)->first();

    expect($raw->request_signing_secret)->not->toBe('request-plain-value')
        ->and($raw->event_signing_secret)->not->toBe('event-plain-value')
        ->and($application->toArray())->not->toHaveKeys(['request_signing_secret', 'event_signing_secret']);
});
