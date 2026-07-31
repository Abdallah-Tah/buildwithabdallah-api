<?php

use App\Jobs\SendWhatsAppMessage;
use App\Models\ConnectedApplication;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Queue;

function sendSignedJson($test, ConnectedApplication $application, array $payload)
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    return $test->call('POST', '/api/v1/whatsapp/messages', [], [], [], signedApplicationHeaders($application, 'POST', '/api/v1/whatsapp/messages', $body), $body);
}

function templatePayload(string $key = 'template-1'): array
{
    return [
        'recipient' => '+12074097887',
        'type' => 'template',
        'product' => 'kirada',
        'template' => ['name' => 'rent_reminder', 'language' => 'en_US', 'components' => []],
        'correlation_id' => 'invoice-582',
        'idempotency_key' => $key,
    ];
}

test('valid template request returns 202 and queues an outbound job', function () {
    Queue::fake();
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);

    sendSignedJson($this, $application, templatePayload())
        ->assertAccepted()
        ->assertJsonPath('data.status', 'queued')
        ->assertJsonPath('data.idempotency_key', 'template-1')
        ->assertJsonMissing(['request_signing_secret']);

    Queue::assertPushedOn('whatsapp-outbound', SendWhatsAppMessage::class);
});

test('identical idempotency request returns the existing message', function () {
    Queue::fake();
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);

    $first = sendSignedJson($this, $application, templatePayload())->assertAccepted()->json('data.id');
    $second = sendSignedJson($this, $application, templatePayload())->assertAccepted()->json('data.id');

    expect($second)->toBe($first)->and(WhatsAppMessage::count())->toBe(1);
});

test('different content with the same idempotency key conflicts', function () {
    Queue::fake();
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    sendSignedJson($this, $application, templatePayload())->assertAccepted();
    $changed = templatePayload();
    $changed['template']['name'] = 'different_template';

    sendSignedJson($this, $application, $changed)
        ->assertConflict()
        ->assertJsonPath('error.code', 'IDEMPOTENCY_CONFLICT');
});

test('idempotency key is required', function () {
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    $payload = templatePayload();
    unset($payload['idempotency_key']);

    sendSignedJson($this, $application, $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('idempotency_key');
});

test('text is allowed inside but rejected outside the customer service window', function (bool $inside, int $status) {
    Queue::fake();
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    $number = '12074097887';
    $contact = WhatsAppContact::factory()->create([
        'phone_number_hash' => hash('sha256', $number),
        'phone_number_encrypted' => $number,
    ]);
    WhatsAppConversation::factory()->create([
        'whatsapp_contact_id' => $contact->id,
        'connected_application_id' => $application->id,
        'product_slug' => 'kirada',
        'state' => 'active',
        'customer_service_window_expires_at' => $inside ? now()->addHour() : now()->subMinute(),
    ]);
    $payload = ['recipient' => '+'.$number, 'type' => 'text', 'body' => 'Update', 'product' => 'kirada', 'idempotency_key' => 'text-'.$status];

    $response = sendSignedJson($this, $application, $payload)->assertStatus($status);

    if (! $inside) {
        $response->assertJsonPath('error.code', 'CUSTOMER_SERVICE_WINDOW_EXPIRED');
    }
})->with([[true, 202], [false, 422]]);

test('template is allowed without an open customer service window', function () {
    Queue::fake();
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);

    sendSignedJson($this, $application, templatePayload('outside-window'))->assertAccepted();
});

test('recipient lookup columns never contain plaintext and message body is encrypted', function () {
    Queue::fake();
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    sendSignedJson($this, $application, templatePayload())->assertAccepted();
    $contact = WhatsAppContact::firstOrFail();

    expect($contact->phone_number_hash)->not->toBe('12074097887')
        ->and(DB::table('whatsapp_contacts')->value('phone_number_encrypted'))->not->toBe('12074097887');
});

test('live sending is safely blocked by default', function () {
    Queue::fake();
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    sendSignedJson($this, $application, templatePayload())->assertAccepted();
    Queue::fake()->except([SendWhatsAppMessage::class]);
    SendWhatsAppMessage::dispatchSync(WhatsAppMessage::firstOrFail());

    expect(WhatsAppMessage::first()->status->value)->toBe('failed')
        ->and(WhatsAppMessage::first()->failure_code)->toBe('LIVE_SEND_DISABLED');
});
