<?php

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Exceptions\MessagingConfigurationException;
use App\Messaging\WhatsAppProviderManager;
use App\Models\WhatsAppMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public array $backoff = [1, 5, 30, 120];

    public function __construct(public WhatsAppMessage $message) {}

    /**
     * Execute the job.
     */
    public function handle(WhatsAppProviderManager $providers): void
    {
        if ($this->message->provider_message_id || $this->message->status !== MessageStatus::Queued) {
            return;
        }

        if (! config('services.whatsapp.live_send_enabled')) {
            $this->message->update([
                'status' => MessageStatus::Failed,
                'failure_code' => 'LIVE_SEND_DISABLED',
                'failure_message' => 'Live WhatsApp sending is disabled.',
                'failed_at' => now(),
            ]);

            return;
        }

        $providerName = $this->message->provider ?: (string) config('services.whatsapp.provider');
        $this->message->update(['provider' => $providerName]);
        $provider = $providers->driver($providerName);
        $recipient = $this->message->contact->phone_number_encrypted;
        $result = $provider->send($this->message, (string) $recipient);
        $this->message->update([
            'provider' => $result->provider,
            'provider_message_id' => $result->messageId,
            'meta_message_id' => $result->provider === 'meta' ? $result->messageId : null,
            'status' => MessageStatus::Accepted,
            'response_payload' => $result->response,
        ]);
        Log::info('whatsapp.message.sent', [
            'internal_message_id' => $this->message->id,
            'provider' => $result->provider,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $providerErrorCode = $exception instanceof RequestException
            ? data_get($exception->response->json(), 'error.code')
            : null;
        $providerErrorMessage = $exception instanceof RequestException
            ? data_get($exception->response->json(), 'error.message')
            : null;

        $this->message->update([
            'status' => MessageStatus::Failed,
            'failure_code' => $exception instanceof MessagingConfigurationException
                ? 'CONFIGURATION_ERROR'
                : (is_scalar($providerErrorCode) ? (string) $providerErrorCode : 'SEND_FAILED'),
            'failure_message' => is_string($providerErrorMessage) && $providerErrorMessage !== ''
                ? Str::limit($providerErrorMessage, 500)
                : 'WhatsApp message delivery failed.',
            'failed_at' => now(),
        ]);
        Log::error('whatsapp.message.failed', [
            'internal_message_id' => $this->message->id,
            'provider' => $this->message->provider,
        ]);
    }
}
