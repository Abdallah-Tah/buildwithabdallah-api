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
            'sent' => $this->sentDriver(),
            default => throw new MessagingConfigurationException("Unsupported WhatsApp provider [{$provider}]."),
        };
    }

    /**
     * Sent.dm is retained for fallback but is off unless deliberately enabled,
     * so a stale provider column or a mistyped env cannot quietly route
     * production traffic away from the Meta Cloud API.
     */
    private function sentDriver(): WhatsAppProvider
    {
        if (! config('services.sent_dm.enabled')) {
            throw new MessagingConfigurationException(
                'The Sent.dm provider is disabled. Set SENT_DM_ENABLED=true to re-enable it.',
            );
        }

        return $this->sent;
    }
}
