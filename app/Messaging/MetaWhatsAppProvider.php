<?php

namespace App\Messaging;

use App\Models\WhatsAppMessage;
use Illuminate\Support\Arr;

class MetaWhatsAppProvider implements WhatsAppProvider
{
    public function __construct(private MetaWhatsAppClient $client) {}

    public function name(): string
    {
        return 'meta';
    }

    public function send(WhatsAppMessage $message, string $recipient): ProviderSendResult
    {
        $response = $this->client->send($message, $recipient);
        $messageId = Arr::get($response, 'messages.0.id');

        if (! is_string($messageId) || $messageId === '') {
            throw new \UnexpectedValueException('Meta accepted the request without returning a message ID.');
        }

        return new ProviderSendResult($this->name(), $messageId, [
            'message_id_present' => true,
        ]);
    }
}
