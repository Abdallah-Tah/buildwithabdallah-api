<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class VerifySentWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.sent_dm.webhook_secret');
        $webhookId = $request->header('X-Webhook-ID');
        $timestamp = $request->header('X-Webhook-Timestamp');
        $provided = $request->header('X-Webhook-Signature');

        if (! is_string($secret) || $secret === ''
            || ! is_string($webhookId) || $webhookId === ''
            || ! is_string($timestamp) || ! ctype_digit($timestamp)
            || ! is_string($provided) || $provided === '') {
            return response('Forbidden', 403);
        }

        $maximumAge = (int) config('services.sent_dm.webhook_signature_max_age_seconds', 300);

        if (abs(Carbon::now()->timestamp - (int) $timestamp) > $maximumAge) {
            return response('Forbidden', 403);
        }

        $encodedKey = str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret;
        $key = base64_decode($encodedKey, true);

        if ($key === false) {
            return response('Forbidden', 403);
        }

        $signed = $webhookId.'.'.$timestamp.'.'.$request->getContent();
        $expected = 'v1,'.base64_encode(hash_hmac('sha256', $signed, $key, true));

        if (! hash_equals($expected, $provided)) {
            return response('Forbidden', 403);
        }

        return $next($request);
    }
}
