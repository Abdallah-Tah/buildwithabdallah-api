<?php

namespace App\Http\Controllers;

use App\Messaging\SentWhatsAppMedia;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SentWhatsAppMediaController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        WhatsAppMessage $message,
        SentWhatsAppMedia $media,
    ): StreamedResponse {
        abort_unless($message->provider === 'sent', 404);
        abort_unless(Storage::disk($media->disk())->exists($media->path($message)), 404);

        return Storage::disk($media->disk())->download(
            $media->path($message),
            $media->filename($message),
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
