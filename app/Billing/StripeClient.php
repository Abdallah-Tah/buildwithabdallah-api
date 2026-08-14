<?php

declare(strict_types=1);

namespace App\Billing;

interface StripeClient
{
    /** @param array<string, mixed> $parameters */
    public function createCustomer(array $parameters, string $idempotencyKey): array;

    /** @param array<string, mixed> $parameters */
    public function updateCustomer(string $customerId, array $parameters): array;

    /** @param array<string, mixed> $parameters */
    public function createCheckoutSession(array $parameters, string $idempotencyKey): array;

    /** @param array<string, mixed> $parameters */
    public function createPortalSession(array $parameters, string $idempotencyKey): array;
}
