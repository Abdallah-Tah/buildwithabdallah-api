<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMetaWhatsAppSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.meta_whatsapp.app_secret');
        $provided = $request->header('X-Hub-Signature-256');

        if (! is_string($secret) || $secret === '' || ! is_string($provided)) {
            return response('Forbidden', 403);
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $provided)) {
            return response('Forbidden', 403);
        }

        return $next($request);
    }
}
