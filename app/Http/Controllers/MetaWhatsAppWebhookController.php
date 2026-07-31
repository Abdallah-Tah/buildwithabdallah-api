<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWhatsAppWebhook;
use App\Models\WhatsAppWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaWhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub.mode', $request->query('hub_mode'));
        $token = $request->query('hub.verify_token', $request->query('hub_verify_token'));
        $challenge = $request->query('hub.challenge', $request->query('hub_challenge'));
        $expectedToken = config('services.meta_whatsapp.verify_token');

        if ($mode !== 'subscribe' || ! is_string($token) || ! is_string($expectedToken) || $expectedToken === '' || ! hash_equals($expectedToken, $token) || ! is_scalar($challenge)) {
            return response('Forbidden', 403);
        }

        return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function store(Request $request): Response
    {
        if (! Str::isJson($request->getContent())) {
            return response('Invalid JSON', 400);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();
        $payloadHash = hash('sha256', $request->getContent());
        $event = WhatsAppWebhookEvent::firstOrCreate(
            ['payload_hash' => $payloadHash],
            [
                'object_type' => Arr::get($payload, 'object'),
                'event_type' => Arr::has($payload, 'entry.0.changes.0.value.statuses') ? 'status' : 'message',
                'raw_payload' => $payload,
                'received_at' => now(),
            ],
        );

        if ($event->wasRecentlyCreated) {
            ProcessWhatsAppWebhook::dispatch($event)->onQueue('whatsapp-webhooks');
            Log::info('whatsapp.webhook.received', ['event_id' => $event->id]);
        } else {
            Log::info('whatsapp.webhook.duplicate', ['event_id' => $event->id]);
        }

        return response('EVENT_RECEIVED');
    }
}
