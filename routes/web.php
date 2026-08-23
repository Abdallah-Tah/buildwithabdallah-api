<?php

use App\Http\Controllers\MetaWhatsAppWebhookController;
use App\Http\Controllers\ReadinessController;
use App\Http\Controllers\SentWhatsAppMediaController;
use App\Http\Controllers\SentWhatsAppWebhookController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('page.home');
Route::view('/about', 'pages.about')->name('page.about');
Route::view('/docs', 'pages.docs')->name('page.docs');
Route::view('/legal', 'pages.legal')->name('page.legal');

Route::get('/webhooks/meta/whatsapp', [MetaWhatsAppWebhookController::class, 'verify'])
    ->name('webhooks.meta.whatsapp.verify');
Route::post('/webhooks/meta/whatsapp', [MetaWhatsAppWebhookController::class, 'store'])
    ->middleware('meta.whatsapp.signature')
    ->name('webhooks.meta.whatsapp.store');
Route::post('/webhooks/sent/whatsapp', SentWhatsAppWebhookController::class)
    ->middleware('sent.whatsapp.signature')
    ->name('webhooks.sent.whatsapp.store');
Route::post('/webhooks/stripe', StripeWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.stripe.store');
Route::get('/media/sent/whatsapp/{message}', SentWhatsAppMediaController::class)
    ->middleware('signed')
    ->name('whatsapp.sent.media.show');

Route::get('/health/ready', ReadinessController::class)->name('health.ready');
