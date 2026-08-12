<?php

namespace App\Messaging;

use App\Exceptions\MessagingConfigurationException;

class WhatsAppProviderManager
{
    public function __construct(
        private MetaWhatsAppProvider $meta,
        private SentWhatsAppProvider $sent,
    ) {}

    public function active(): WhatsAppProvider
    {
        return $this->driver((string) config('services.whatsapp.provider'));
    }

    public function driver(string $provider): WhatsAppProvider
    {
        return match ($provider) {
            'meta' => $this->meta,
            'sent' => $this->sent,
            default => throw new MessagingConfigurationException("Unsupported WhatsApp provider [{$provider}]."),
        };
    }
}
