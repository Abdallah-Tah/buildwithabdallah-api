<?php

use App\Http\Controllers\MetaWhatsAppWebhookController;
use App\Http\Controllers\ReadinessController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/webhooks/meta/whatsapp', [MetaWhatsAppWebhookController::class, 'verify'])
    ->name('webhooks.meta.whatsapp.verify');
Route::post('/webhooks/meta/whatsapp', [MetaWhatsAppWebhookController::class, 'store'])
    ->middleware('meta.whatsapp.signature')
    ->name('webhooks.meta.whatsapp.store');

Route::get('/health/ready', ReadinessController::class)->name('health.ready');
