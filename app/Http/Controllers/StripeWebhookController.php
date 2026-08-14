<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Billing\RouteStripeEvent;
use App\Billing\StripeWebhookSignature;
use App\Models\StripeWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JsonException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeWebhookSignature $signature, RouteStripeEvent $route): JsonResponse
    {
        $payload = $request->getContent();
        if (! $signature->verify(
            $payload,
            (string) $request->header('Stripe-Signature'),
            (string) config('services.stripe.webhook_secret'),
            (int) config('services.stripe.webhook_tolerance_seconds', 300),
        )) {
            return response()->json(['error' => ['code' => 'INVALID_SIGNATURE']], Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json(['error' => ['code' => 'INVALID_JSON']], Response::HTTP_BAD_REQUEST);
        }

        if (! is_array($event) || ! is_string($event['id'] ?? null) || ! is_string($event['type'] ?? null)) {
            return response()->json(['error' => ['code' => 'INVALID_EVENT']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $stored = StripeWebhookEvent::query()->firstOrCreate(
            ['stripe_event_id' => $event['id']],
            [
                'type' => $event['type'],
                'livemode' => (bool) ($event['livemode'] ?? false),
                'payload' => $payload,
            ],
        );

        if (! $stored->wasRecentlyCreated) {
            return response()->json(['received' => true, 'duplicate' => true]);
        }

        try {
            $route->handle($event);
            $stored->update(['status' => 'processed', 'processed_at' => now()]);
        } catch (Throwable $exception) {
            $stored->update(['status' => 'failed', 'error_message' => Str::limit($exception->getMessage(), 1000)]);
            throw $exception;
        }

        return response()->json(['received' => true, 'duplicate' => false]);
    }
}
