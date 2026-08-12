<?php

namespace App\Messaging;

use App\Exceptions\MessagingConfigurationException;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class SentWhatsAppProvider implements WhatsAppProvider
{
    public function __construct(private SentWhatsAppMedia $media) {}

    public function name(): string
    {
        return 'sent';
    }

    public function send(WhatsAppMessage $message, string $recipient): ProviderSendResult
    {
        $response = $this->request($message)
            ->post('/v3/messages', $this->payload($message, $recipient))
            ->throw()
            ->json();
        $messageId = Arr::get($response, 'data.recipients.0.message_id');

        if (! is_string($messageId) || $messageId === '') {
            throw new \UnexpectedValueException('Sent accepted the request without returning a message ID.');
        }

        return new ProviderSendResult($this->name(), $messageId, [
            'request_id' => Arr::get($response, 'meta.request_id'),
            'status' => Arr::get($response, 'data.status'),
            'channel' => Arr::get($response, 'data.recipients.0.channel'),
        ]);
    }

    private function request(WhatsAppMessage $message): PendingRequest
    {
        $apiKey = config('services.sent_dm.api_key');
        $baseUrl = config('services.sent_dm.base_url');

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($baseUrl) || $baseUrl === '') {
            throw new MessagingConfigurationException('Sent WhatsApp client configuration is incomplete.');
        }

        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->withHeaders([
                'x-api-key' => $apiKey,
                'Idempotency-Key' => (string) ($message->idempotency_key ?: $message->id),
            ])
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

    /** @return array<string, mixed> */
    private function payload(WhatsAppMessage $message, string $recipient): array
    {
        $payload = [
            'to' => ['+'.ltrim($recipient, '+')],
            'channel' => ['whatsapp'],
            'sandbox' => (bool) config('services.sent_dm.sandbox', false),
        ];

        if ($message->message_type === 'template') {
            $payload['template'] = $this->templateReference($message);
        } else {
            $payload['text'] = (string) $message->text_body_encrypted;
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function templateReference(WhatsAppMessage $message): array
    {
        $configuration = Arr::get(
            config('services.sent_dm.template_map', []),
            $message->template_name.'.'.$message->template_language,
            Arr::get(config('services.sent_dm.template_map', []), $message->template_name),
        );
        $reference = is_array($configuration) ? $configuration : [];
        $identifier = is_string($configuration) ? $configuration : Arr::get($reference, 'id');
        $name = Arr::get($reference, 'name', $message->template_name);
        $parameterNames = Arr::get($reference, 'parameters', []);

        $template = [];

        if (is_string($identifier) && $identifier !== '') {
            $template['id'] = $identifier;
        } elseif (is_string($name) && $name !== '') {
            $template['name'] = $name;
        } else {
            throw new MessagingConfigurationException('A Sent template ID or name is required.');
        }

        $parameters = $this->parameters($message, is_array($parameterNames) ? $parameterNames : []);

        if ($parameters !== []) {
            $template['parameters'] = $parameters;
        }

        return $template;
    }

    /**
     * @param  array<int, string>  $parameterNames
     * @return array<string, string>
     */
    private function parameters(WhatsAppMessage $message, array $parameterNames): array
    {
        $parameters = [];
        $position = 0;

        foreach (Arr::get($message->request_payload, 'template.components', []) as $component) {
            foreach (Arr::get($component, 'parameters', []) as $parameter) {
                $position++;
                $key = $parameterNames[$position - 1] ?? (string) $position;
                $parameters[$key] = match (Arr::get($parameter, 'type')) {
                    'text' => (string) Arr::get($parameter, 'text', ''),
                    'document' => $this->media->store($message, (array) Arr::get($parameter, 'document', [])),
                    default => throw new MessagingConfigurationException(
                        'The Sent template parameter type is unsupported.',
                    ),
                };
            }
        }

        return $parameters;
    }
}
