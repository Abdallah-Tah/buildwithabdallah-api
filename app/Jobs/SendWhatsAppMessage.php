<?php

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Exceptions\MessagingConfigurationException;
use App\Messaging\MetaWhatsAppClient;
use App\Models\WhatsAppMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
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
    public function handle(MetaWhatsAppClient $client): void
    {
        if ($this->message->meta_message_id || $this->message->status !== MessageStatus::Queued) {
            return;
        }

        if (! config('services.meta_whatsapp.live_send_enabled')) {
            $this->message->update([
                'status' => MessageStatus::Failed,
                'failure_code' => 'LIVE_SEND_DISABLED',
                'failure_message' => 'Live WhatsApp sending is disabled.',
                'failed_at' => now(),
            ]);

            return;
        }

        $recipient = $this->message->contact->phone_number_encrypted;
        $result = $client->send($this->message, (string) $recipient);
        $this->message->update([
            'meta_message_id' => Arr::get($result, 'messages.0.id'),
            'status' => MessageStatus::Accepted,
            'response_payload' => ['message_id_present' => Arr::has($result, 'messages.0.id')],
        ]);
        Log::info('whatsapp.message.sent', ['internal_message_id' => $this->message->id]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->message->update([
            'status' => MessageStatus::Failed,
            'failure_code' => $exception instanceof MessagingConfigurationException ? 'CONFIGURATION_ERROR' : 'SEND_FAILED',
            'failure_message' => 'WhatsApp message delivery failed.',
            'failed_at' => now(),
        ]);
        Log::error('whatsapp.message.failed', ['internal_message_id' => $this->message->id]);
    }
}
