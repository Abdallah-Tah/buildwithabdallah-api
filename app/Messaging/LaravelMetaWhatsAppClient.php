<?php

namespace App\Messaging;

use App\Exceptions\MessagingConfigurationException;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class LaravelMetaWhatsAppClient implements MetaWhatsAppClient
{
    public function send(WhatsAppMessage $message, string $recipient): array
    {
        if (! config('services.meta_whatsapp.live_send_enabled')) {
            throw new MessagingConfigurationException('Live WhatsApp sending is disabled.');
        }

        $payload = $message->message_type === 'template'
            ? [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'template',
                'template' => [
                    'name' => $message->template_name,
                    'language' => ['code' => $message->template_language],
                    'components' => $this->prepareTemplateComponents(
                        $message->request_payload['template']['components'] ?? [],
                    ),
                ],
            ]
            : [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'text',
                'text' => ['body' => $message->text_body_encrypted],
            ];

        $response = $this->request()->post($this->endpoint('messages'), $payload);

        return $response->throw()->json();
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     * @return array<int, array<string, mixed>>
     */
    private function prepareTemplateComponents(array $components): array
    {
        foreach ($components as $componentIndex => $component) {
            foreach (Arr::get($component, 'parameters', []) as $parameterIndex => $parameter) {
                $document = Arr::get($parameter, 'document');

                if (! is_array($document) || ! isset($document['content_base64'])) {
                    continue;
                }

                $components[$componentIndex]['parameters'][$parameterIndex]['document'] = $this->uploadDocument($document);
            }
        }

        return $components;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array{id: string, filename?: string}
     */
    private function uploadDocument(array $document): array
    {
        $contents = base64_decode((string) $document['content_base64'], true);

        if ($contents === false) {
            throw new InvalidArgumentException('The WhatsApp document content is not valid base64.');
        }

        $filename = is_string($document['filename'] ?? null) && $document['filename'] !== ''
            ? $document['filename']
            : 'document.pdf';
        $contentType = is_string($document['content_type'] ?? null) && $document['content_type'] !== ''
            ? $document['content_type']
            : 'application/pdf';

        $response = $this->request()
            ->attach('file', $contents, $filename, ['Content-Type' => $contentType])
            ->post($this->endpoint('media'), [
                'messaging_product' => 'whatsapp',
                'type' => $contentType,
            ])
            ->throw();

        $mediaId = $response->json('id');

        if (! is_string($mediaId) || $mediaId === '') {
            throw new RequestException($response);
        }

        return ['id' => $mediaId, 'filename' => $filename];
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

    private function endpoint(string $resource): string
    {
        $version = config('services.meta_whatsapp.graph_api_version');
        $phoneNumberId = config('services.meta_whatsapp.phone_number_id');

        if (! is_string($version) || $version === '' || ! is_string($phoneNumberId) || $phoneNumberId === '') {
            throw new MessagingConfigurationException('Meta WhatsApp client configuration is incomplete.');
        }

        return "https://graph.facebook.com/{$version}/{$phoneNumberId}/{$resource}";
    }
}
