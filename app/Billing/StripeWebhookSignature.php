<?php

declare(strict_types=1);

namespace App\Billing;

class StripeWebhookSignature
{
    public function verify(string $payload, string $header, string $secret, int $tolerance): bool
    {
        if ($payload === '' || $header === '' || $secret === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key][] = $value;
            }
        }

        $timestamp = $parts['t'][0] ?? null;
        if (! is_string($timestamp) || ! ctype_digit($timestamp) || abs(now()->timestamp - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($parts['v1'] ?? [] as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
