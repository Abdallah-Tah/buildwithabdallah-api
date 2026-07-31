<?php

use App\Models\ConnectedApplication;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppWebhookEvent;

test('readiness reports safe internal check names', function () {
    $this->getJson('/health/ready')
        ->assertSuccessful()
        ->assertExactJson([
            'status' => 'ready',
            'checks' => ['database' => 'ok', 'cache' => 'ok', 'queue' => 'ok'],
        ])
        ->assertJsonMissing(['host', 'database_name', 'credentials', 'exception']);
});

test('application create stores encrypted secrets', function () {
    $this->artisan('bwa:application:create', [
        '--name' => 'Kirada',
        '--slug' => 'kirada',
        '--webhook-url' => 'https://kirada.example.test/events',
    ])->assertSuccessful();

    $application = ConnectedApplication::firstOrFail();
    $raw = DB::table('connected_applications')->first();

    expect($application->request_signing_secret)->not->toBeEmpty()
        ->and($raw->request_signing_secret)->not->toBe($application->request_signing_secret);
});

test('rotating request secret invalidates the old secret', function () {
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    $old = $application->request_signing_secret;

    $this->artisan('bwa:application:rotate-secret', ['slug' => 'kirada', '--type' => 'request'])->assertSuccessful();

    expect($application->fresh()->request_signing_secret)->not->toBe($old);
});

test('disable and list commands do not expose secrets', function () {
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada', 'request_signing_secret' => 'never-display-this']);
    $this->artisan('bwa:application:disable', ['slug' => 'kirada'])->assertSuccessful();
    $this->artisan('bwa:application:list')->doesntExpectOutputToContain('never-display-this')->assertSuccessful();

    expect($application->fresh()->enabled)->toBeFalse();
});

test('prune dry run preserves payload and actual prune preserves normalized messages', function () {
    config()->set('services.meta_whatsapp.webhook_retention_days', 30);
    $event = WhatsAppWebhookEvent::factory()->create(['received_at' => now()->subDays(31), 'raw_payload' => ['sensitive' => true]]);
    $message = WhatsAppMessage::factory()->create();

    $this->artisan('bwa:whatsapp:prune-webhooks', ['--dry-run' => true])->assertSuccessful();
    expect($event->fresh()->raw_payload)->not->toBeNull();

    $this->artisan('bwa:whatsapp:prune-webhooks')->assertSuccessful();
    expect($event->fresh()->raw_payload)->toBeNull()->and($message->fresh())->not->toBeNull();
});

test('manual smoke send refuses while live sending is disabled', function () {
    config()->set('services.meta_whatsapp.live_send_enabled', false);

    $this->artisan('bwa:whatsapp:test-send', ['recipient' => '+12074097887', 'message' => 'test'])
        ->assertFailed();
});
