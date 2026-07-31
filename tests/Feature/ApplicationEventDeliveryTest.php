<?php

use App\Jobs\DispatchApplicationEvent;
use App\Messaging\ApplicationEventDispatcher;
use App\Messaging\HmacSigner;
use App\Models\ApplicationEventDelivery;
use App\Models\ConnectedApplication;
use Illuminate\Support\Facades\Http;

test('application events are signed and 200 or 202 responses are recorded', function (int $status) {
    Http::fake(['https://example.test/*' => Http::response([], $status)]);
    $application = ConnectedApplication::factory()->create(['slug' => 'kirada']);
    $delivery = ApplicationEventDelivery::factory()->create(['connected_application_id' => $application->id]);

    (new DispatchApplicationEvent($delivery))->handle(app(ApplicationEventDispatcher::class));

    expect($delivery->fresh()->status)->toBe('delivered')
        ->and($delivery->fresh()->response_status)->toBe($status)
        ->and($delivery->fresh()->delivered_at)->not->toBeNull();

    Http::assertSent(function ($request) use ($delivery): bool {
        $body = $request->body();
        $timestamp = $request->header('X-BWA-Timestamp')[0];
        $signature = app(HmacSigner::class)->sign(
            'POST',
            '/api/internal/bwa/whatsapp/events',
            $timestamp,
            $delivery->event_id,
            $body,
            $delivery->connectedApplication->event_signing_secret,
        );

        return $request->header('X-BWA-Signature')[0] === $signature
            && $request->header('X-BWA-Request-ID')[0] === $delivery->event_id;
    });
})->with([200, 202]);

test('permanent application failure is recorded without throwing', function () {
    Http::fake(['https://example.test/*' => Http::response([], 422)]);
    $delivery = ApplicationEventDelivery::factory()->create();

    (new DispatchApplicationEvent($delivery))->handle(app(ApplicationEventDispatcher::class));

    expect($delivery->fresh()->status)->toBe('failed')
        ->and($delivery->fresh()->response_status)->toBe(422)
        ->and($delivery->fresh()->attempt_count)->toBe(1);
});

test('transient application failures throw for queue retry', function () {
    Http::fake(['https://example.test/*' => Http::response([], 503)]);
    $delivery = ApplicationEventDelivery::factory()->create();

    expect(fn () => (new DispatchApplicationEvent($delivery))->handle(app(ApplicationEventDispatcher::class)))
        ->toThrow(RuntimeException::class);
});
