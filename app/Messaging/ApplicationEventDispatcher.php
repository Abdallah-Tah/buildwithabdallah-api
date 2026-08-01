<?php

namespace App\Messaging;

use App\Models\ApplicationEventDelivery;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ApplicationEventDispatcher
{
    public function __construct(private HmacSigner $signer) {}

    public function dispatch(ApplicationEventDelivery $delivery): Response
    {
        $application = $delivery->connectedApplication;
        $payload = $delivery->payload;
        $payload['id'] ??= $delivery->event_id;
        $payload['event_id'] = $delivery->event_id;
        $payload['type'] ??= $delivery->event_type;
        $payload['event_type'] ??= $delivery->event_type;
        $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $timestamp = (string) now()->timestamp;
        $requestId = (string) Str::uuid();
        $path = (string) parse_url((string) $application->webhook_url, PHP_URL_PATH);
        $signature = $this->signer->sign('POST', $path, $timestamp, $requestId, $body, $application->event_signing_secret);

        return Http::withHeaders([
            'X-BWA-Source' => 'buildwithabdallah-api',
            'X-BWA-Timestamp' => $timestamp,
            'X-BWA-Request-ID' => $requestId,
            'X-BWA-Signature' => $signature,
            'Content-Type' => 'application/json',
        ])->timeout((int) config('services.meta_whatsapp.application_event_timeout_seconds', 10))
            ->withBody($body, 'application/json')
            ->post((string) $application->webhook_url);
    }
}
