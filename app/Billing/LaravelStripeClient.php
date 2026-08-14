<?php

declare(strict_types=1);

namespace App\Billing;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use LogicException;

class LaravelStripeClient implements StripeClient
{
    public function createCustomer(array $parameters, string $idempotencyKey): array
    {
        return $this->request($idempotencyKey)->post('/v1/customers', $parameters)->throw()->json();
    }

    public function updateCustomer(string $customerId, array $parameters): array
    {
        return $this->request()->post('/v1/customers/'.$customerId, $parameters)->throw()->json();
    }

    public function createCheckoutSession(array $parameters, string $idempotencyKey): array
    {
        return $this->request($idempotencyKey)->post('/v1/checkout/sessions', $parameters)->throw()->json();
    }

    public function createPortalSession(array $parameters, string $idempotencyKey): array
    {
        return $this->request($idempotencyKey)->post('/v1/billing_portal/sessions', $parameters)->throw()->json();
    }

    private function request(?string $idempotencyKey = null): PendingRequest
    {
        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            throw new LogicException('Stripe is not configured.');
        }

        $request = Http::baseUrl(rtrim((string) config('services.stripe.base_url'), '/'))
            ->asForm()
            ->acceptJson()
            ->withBasicAuth($secret, '')
            ->connectTimeout(5)
            ->timeout(20)
            ->retry([200, 500], throw: false);

        return $idempotencyKey
            ? $request->withHeader('Idempotency-Key', $idempotencyKey)
            : $request;
    }
}
