<?php

namespace App\Messaging;

final readonly class ProviderSendResult
{
    /** @param array<string, mixed> $response */
    public function __construct(
        public string $provider,
        public string $messageId,
        public array $response = [],
    ) {}
}
