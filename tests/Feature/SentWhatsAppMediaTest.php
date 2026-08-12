<?php

use App\Messaging\SentWhatsAppMedia;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

test('Sent can download a private PDF only through its temporary signed URL', function () {
    Storage::fake('local');
    $message = WhatsAppMessage::factory()->create([
        'provider' => 'sent',
        'request_payload' => [
            'template' => [
                'components' => [[
                    'parameters' => [[
                        'type' => 'document',
                        'document' => ['filename' => 'rent invoice.pdf'],
                    ]],
                ]],
            ],
        ],
    ]);
    $media = app(SentWhatsAppMedia::class);
    Storage::disk('local')->put($media->path($message), '%PDF-1.4 private invoice');

    $this->get(route('whatsapp.sent.media.show', $message))->assertForbidden();

    $url = URL::temporarySignedRoute(
        'whatsapp.sent.media.show',
        now()->addMinute(),
        ['message' => $message],
    );
    $this->get($url)
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('rent-invoice.pdf');
});
