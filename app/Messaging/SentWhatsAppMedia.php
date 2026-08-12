<?php

namespace App\Messaging;

use App\Exceptions\MessagingConfigurationException;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SentWhatsAppMedia
{
    /** @param array<string, mixed> $document */
    public function store(WhatsAppMessage $message, array $document): string
    {
        if (! $message->exists) {
            throw new MessagingConfigurationException('A persisted WhatsApp message is required for Sent media.');
        }

        $encoded = data_get($document, 'content_base64');
        $contents = is_string($encoded) ? base64_decode($encoded, true) : false;

        if (! is_string($contents) || $contents === '') {
            throw new MessagingConfigurationException('Sent document content must be valid base64.');
        }

        if (strlen($contents) > (int) config('services.sent_dm.media_max_bytes', 10 * 1024 * 1024)) {
            throw new MessagingConfigurationException('Sent document content exceeds the configured size limit.');
        }

        $mimeType = (string) data_get($document, 'mime_type', 'application/pdf');

        if ($mimeType !== 'application/pdf' || ! str_starts_with($contents, '%PDF-')) {
            throw new MessagingConfigurationException('Sent document media must be a valid PDF.');
        }

        Storage::disk($this->disk())->put($this->path($message), $contents);

        return URL::temporarySignedRoute(
            'whatsapp.sent.media.show',
            now()->addMinutes((int) config('services.sent_dm.media_url_expiration_minutes', 60)),
            ['message' => $message],
        );
    }

    public function delete(WhatsAppMessage $message): void
    {
        Storage::disk($this->disk())->delete($this->path($message));
    }

    public function path(WhatsAppMessage $message): string
    {
        return 'sent-whatsapp-documents/'.$message->id.'.pdf';
    }

    public function filename(WhatsAppMessage $message): string
    {
        $parameter = collect(data_get($message->request_payload, 'template.components', []))
            ->flatMap(fn (array $component): array => data_get($component, 'parameters', []))
            ->firstWhere('type', 'document');

        return Str::of((string) data_get($parameter, 'document.filename', 'kirada-document.pdf'))
            ->basename()
            ->replaceMatches('/[^A-Za-z0-9._-]/', '-')
            ->limit(180, '')
            ->toString();
    }

    public function disk(): string
    {
        return (string) config('services.sent_dm.media_disk', 'local');
    }
}
