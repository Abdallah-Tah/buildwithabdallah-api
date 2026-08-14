<?php

use App\Models\BillingCustomer;
use App\Models\ConnectedApplication;
use Illuminate\Support\Facades\Http;

function sendSignedBillingJson($test, ConnectedApplication $application, string $path, array $payload)
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

    return $test->call('POST', $path, [], [], [], signedApplicationHeaders($application, 'POST', $path, $body), $body);
}

function billingApplication(): ConnectedApplication
{
    return ConnectedApplication::factory()->create([
        'slug' => 'kirada',
        'webhook_url' => 'https://www.kirada.net/api/internal/bwa/whatsapp/events',
        'metadata' => [
            'billing_allowed_redirect_hosts' => ['www.kirada.net'],
            'billing_webhook_url' => 'https://www.kirada.net/api/internal/bwa/billing/events',
        ],
    ]);
}

function checkoutPayload(): array
{
    return [
        'external_customer_id' => '42',
        'customer' => ['email' => 'landlord@example.com', 'name' => 'Landlord'],
        'plan' => ['id' => 'growth', 'name' => 'Kirada Growth', 'amount' => 15000, 'currency' => 'DJF', 'interval' => 'month'],
        'success_url' => 'https://www.kirada.net/subscription?checkout=success',
        'cancel_url' => 'https://www.kirada.net/subscription?checkout=cancel',
        'idempotency_key' => 'kirada-user-42-growth',
    ];
}

test('a connected application creates a central Stripe checkout session', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.stripe.com/v1/customers' => Http::response(['id' => 'cus_central']),
        'api.stripe.com/v1/checkout/sessions' => Http::response([
            'id' => 'cs_test_central',
            'url' => 'https://checkout.stripe.com/c/pay/test',
            'customer' => 'cus_central',
        ]),
    ]);
    config()->set('services.stripe.secret', 'sk_test_central');
    $application = billingApplication();

    sendSignedBillingJson($this, $application, '/api/v1/billing/checkout-sessions', checkoutPayload())
        ->assertCreated()
        ->assertJsonPath('data.id', 'cs_test_central')
        ->assertJsonPath('data.url', 'https://checkout.stripe.com/c/pay/test');

    expect(BillingCustomer::firstOrFail())
        ->external_customer_id->toBe('42')
        ->stripe_customer_id->toBe('cus_central');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.stripe.com/v1/checkout/sessions'
        && $request['subscription_data']['metadata']['bwa_app'] === 'kirada'
        && $request['line_items'][0]['price_data']['unit_amount'] === 15000);
});

test('billing redirects are restricted to the connected application hosts', function () {
    Http::preventStrayRequests();
    config()->set('services.stripe.secret', 'sk_test_central');
    $application = billingApplication();
    $payload = checkoutPayload();
    $payload['success_url'] = 'https://attacker.example/collect';

    sendSignedBillingJson($this, $application, '/api/v1/billing/checkout-sessions', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('redirect_url');

    Http::assertNothingSent();
});

test('the billing portal uses the central Stripe customer mapping', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.stripe.com/v1/billing_portal/sessions' => Http::response([
            'id' => 'bps_test_central',
            'url' => 'https://billing.stripe.com/p/session/test',
        ]),
    ]);
    config()->set('services.stripe.secret', 'sk_test_central');
    $application = billingApplication();
    BillingCustomer::create([
        'connected_application_id' => $application->id,
        'external_customer_id' => '42',
        'stripe_customer_id' => 'cus_central',
        'email' => 'landlord@example.com',
        'name' => 'Landlord',
    ]);

    sendSignedBillingJson($this, $application, '/api/v1/billing/portal-sessions', [
        'external_customer_id' => '42',
        'return_url' => 'https://www.kirada.net/subscription',
        'idempotency_key' => 'portal-42-1',
    ])->assertCreated()->assertJsonPath('data.url', 'https://billing.stripe.com/p/session/test');
});
