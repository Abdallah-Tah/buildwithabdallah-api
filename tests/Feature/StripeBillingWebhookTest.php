<?php

use App\Jobs\DispatchApplicationEvent;
use App\Models\ApplicationEventDelivery;
use App\Models\BillingCustomer;
use App\Models\ConnectedApplication;
use App\Models\StripeWebhookEvent;
use Illuminate\Support\Facades\Queue;

function stripeSignature(string $payload, string $secret, ?int $timestamp = null): string
{
    $timestamp ??= now()->timestamp;

    return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
}

test('verified Stripe events are stored once and routed to the owning application', function () {
    Queue::fake();
    config()->set('services.stripe.webhook_secret', 'whsec_test');
    $application = ConnectedApplication::factory()->create([
        'slug' => 'kirada',
        'webhook_url' => 'https://www.kirada.net/api/internal/bwa/whatsapp/events',
        'metadata' => ['billing_webhook_url' => 'https://www.kirada.net/api/internal/bwa/billing/events'],
    ]);
    BillingCustomer::create([
        'connected_application_id' => $application->id,
        'external_customer_id' => '42',
        'stripe_customer_id' => 'cus_central',
    ]);
    $event = [
        'id' => 'evt_central_1',
        'type' => 'customer.subscription.updated',
        'created' => now()->timestamp,
        'livemode' => false,
        'data' => ['object' => [
            'id' => 'sub_central',
            'object' => 'subscription',
            'customer' => 'cus_central',
            'status' => 'active',
            'current_period_end' => now()->addMonth()->timestamp,
            'metadata' => ['bwa_external_plan_id' => 'growth'],
        ]],
    ];
    $payload = json_encode($event, JSON_THROW_ON_ERROR);
    $headers = [
        'HTTP_STRIPE_SIGNATURE' => stripeSignature($payload, 'whsec_test'),
        'CONTENT_TYPE' => 'application/json',
    ];

    $this->call('POST', '/webhooks/stripe', [], [], [], $headers, $payload)
        ->assertOk()
        ->assertJson(['received' => true, 'duplicate' => false]);
    $this->call('POST', '/webhooks/stripe', [], [], [], $headers, $payload)
        ->assertOk()
        ->assertJson(['received' => true, 'duplicate' => true]);

    expect(StripeWebhookEvent::count())->toBe(1)
        ->and(ApplicationEventDelivery::count())->toBe(1)
        ->and(ApplicationEventDelivery::firstOrFail()->event_type)->toBe('billing.stripe.customer.subscription.updated');
    Queue::assertPushedOn('application-events', DispatchApplicationEvent::class);
});

test('invalid Stripe signatures are rejected before persistence', function () {
    config()->set('services.stripe.webhook_secret', 'whsec_test');
    $payload = json_encode(['id' => 'evt_invalid', 'type' => 'invoice.paid'], JSON_THROW_ON_ERROR);

    $this->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 't='.now()->timestamp.',v1=invalid',
        'CONTENT_TYPE' => 'application/json',
    ], $payload)->assertBadRequest();

    expect(StripeWebhookEvent::count())->toBe(0);
});
