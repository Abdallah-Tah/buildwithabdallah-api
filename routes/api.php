<?php

use App\Http\Controllers\Api\V1\BillingCheckoutController;
use App\Http\Controllers\Api\V1\BillingPortalController;
use App\Http\Controllers\Api\V1\WhatsAppConversationController;
use App\Http\Controllers\Api\V1\WhatsAppMessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/whatsapp')->middleware('bwa.application')->group(function (): void {
    Route::post('/messages', [WhatsAppMessageController::class, 'store'])->name('api.v1.whatsapp.messages.store');
    Route::get('/messages/{message}', [WhatsAppMessageController::class, 'show'])->name('api.v1.whatsapp.messages.show');
    Route::get('/conversations/{conversation}', [WhatsAppConversationController::class, 'show'])->name('api.v1.whatsapp.conversations.show');
    Route::post('/conversations/{conversation}/route', [WhatsAppConversationController::class, 'route'])->name('api.v1.whatsapp.conversations.route');
});

Route::prefix('v1/billing')->middleware('bwa.application')->group(function (): void {
    Route::post('/checkout-sessions', BillingCheckoutController::class)->name('api.v1.billing.checkout-sessions.store');
    Route::post('/portal-sessions', BillingPortalController::class)->name('api.v1.billing.portal-sessions.store');
});
