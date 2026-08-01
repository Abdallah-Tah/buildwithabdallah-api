<?php

namespace App\Messaging;

use App\Exceptions\MessagingConfigurationException;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class LaravelMetaWhatsAppClient implements MetaWhatsAppClient
{
    public function send(WhatsAppMessage $message, string $recipient): array
    {
        if (! config('services.meta_whatsapp.live_send_enabled')) {
            throw new MessagingConfigurationException('Live WhatsApp sending is disabled.');
        }

        $request = $this->request();
        $payload = $message->message_type === 'template'
            ? [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'template',
                'template' => [
                    'name' => $message->template_name,
                    'language' => ['code' => $message->template_language],
                    'components' => $message->request_payload['template']['components'] ?? [],
                ],
            ]
            : [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'text',
                'text' => ['body' => $message->text_body_encrypted],
            ];

        $response = $request->post($this->endpoint(), $payload);

        return $response->throw()->json();
    }

    private function request(): PendingRequest
    {
        $token = config('services.meta_whatsapp.access_token');

        if (! is_string($token) || $token === '') {
            throw new MessagingConfigurationException('Meta WhatsApp client configuration is incomplete.');
        }

        return Http::withToken($token)
            ->acceptJson()
            ->timeout(15)
            ->retry(
                [200, 1000],
                fn (\Throwable $exception, PendingRequest $request): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException
                        && ($exception->response->serverError() || $exception->response->status() === 429)),
                throw: false,
            );
    }

    private function endpoint(): string
    {
        $version = config('services.meta_whatsapp.graph_api_version');
        $phoneNumberId = config('services.meta_whatsapp.phone_number_id');

        if (! is_string($version) || $version === '' || ! is_string($phoneNumberId) || $phoneNumberId === '') {
            throw new MessagingConfigurationException('Meta WhatsApp client configuration is incomplete.');
        }

        return "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";
    }
}
