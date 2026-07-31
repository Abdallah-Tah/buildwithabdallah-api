<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Messaging\HmacSigner;
use App\Models\ApplicationRequestNonce;
use App\Models\ConnectedApplication;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateConnectedApplication
{
    public function __construct(private HmacSigner $signer) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->header('X-BWA-App');
        $timestamp = $request->header('X-BWA-Timestamp');
        $requestId = $request->header('X-BWA-Request-ID');
        $provided = $request->header('X-BWA-Signature');
        $application = is_string($slug) ? ConnectedApplication::query()->where('slug', $slug)->first() : null;

        if (! $application || ! is_string($timestamp) || ! ctype_digit($timestamp) || ! is_string($requestId) || ! is_string($provided)) {
            return $this->error('UNAUTHENTICATED', 'Application authentication failed.', 401);
        }

        if (! $application->enabled) {
            return $this->error('APPLICATION_DISABLED', 'The connected application is disabled.', 403);
        }

        $maximumAge = (int) config('services.meta_whatsapp.internal_signature_max_age_seconds', 300);

        if (abs(now()->timestamp - (int) $timestamp) > $maximumAge) {
            return $this->error('UNAUTHENTICATED', 'Application authentication failed.', 401);
        }

        if (ApplicationRequestNonce::query()->whereBelongsTo($application)->where('request_id', $requestId)->exists()) {
            return $this->error('REPLAYED_REQUEST', 'This request ID has already been used.', 409);
        }

        $expected = $this->signer->sign($request->method(), $request->getPathInfo(), $timestamp, $requestId, $request->getContent(), $application->request_signing_secret);

        if (! hash_equals($expected, $provided)) {
            return $this->error('UNAUTHENTICATED', 'Application authentication failed.', 401);
        }

        $rateKey = 'bwa-application:'.$application->id;

        if (RateLimiter::tooManyAttempts($rateKey, 120)) {
            return $this->error('RATE_LIMITED', 'Too many requests.', 429);
        }

        RateLimiter::hit($rateKey, 60);

        try {
            ApplicationRequestNonce::create([
                'connected_application_id' => $application->id,
                'request_id' => $requestId,
                'timestamp' => now()->setTimestamp((int) $timestamp),
                'body_hash' => $this->signer->bodyHash($request->getContent()),
                'expires_at' => now()->addSeconds($maximumAge),
            ]);
        } catch (QueryException) {
            return $this->error('REPLAYED_REQUEST', 'This request ID has already been used.', 409);
        }

        $request->attributes->set('connected_application', $application);
        $request->attributes->set('bwa_request_id', $requestId);

        return $next($request);
    }

    private function error(string $code, string $message, int $status): Response
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
