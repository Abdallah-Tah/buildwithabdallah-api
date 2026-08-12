<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWhatsAppWebhook;
use App\Models\WhatsAppWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SentWhatsAppWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! Str::isJson($request->getContent())) {
            return response()->json(['received' => false], 400);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();
        $payloadHash = hash('sha256', 'sent:'.$request->getContent());
        $event = WhatsAppWebhookEvent::firstOrCreate(
            ['payload_hash' => $payloadHash],
            [
                'provider' => 'sent',
                'object_type' => data_get($payload, 'field'),
                'event_type' => data_get($payload, 'event', data_get($payload, 'sub_type')),
                'raw_payload' => $payload,
                'received_at' => now(),
            ],
        );

        if ($event->wasRecentlyCreated) {
            ProcessWhatsAppWebhook::dispatch($event)->onQueue('whatsapp-webhooks');
            Log::info('whatsapp.webhook.received', ['event_id' => $event->id, 'provider' => 'sent']);
        } else {
            Log::info('whatsapp.webhook.duplicate', ['event_id' => $event->id, 'provider' => 'sent']);
        }

        return response()->json(['received' => true]);
    }
}
