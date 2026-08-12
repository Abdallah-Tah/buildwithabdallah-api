<?php

use App\Http\Middleware\AuthenticateConnectedApplication;
use App\Http\Middleware\VerifyMetaWhatsAppSignature;
use App\Http\Middleware\VerifySentWebhookSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->preventRequestForgery(except: [
            'webhooks/meta/whatsapp',
            'webhooks/sent/whatsapp',
        ]);

        $middleware->alias([
            'meta.whatsapp.signature' => VerifyMetaWhatsAppSignature::class,
            'sent.whatsapp.signature' => VerifySentWebhookSignature::class,
            'bwa.application' => AuthenticateConnectedApplication::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
