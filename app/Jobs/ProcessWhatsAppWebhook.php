<?php

namespace App\Jobs;

use App\Messaging\WhatsAppWebhookProcessor;
use App\Models\WhatsAppWebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessWhatsAppWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public array $backoff = [1, 5, 30, 120];

    public function __construct(public WhatsAppWebhookEvent $event) {}

    /**
     * Execute the job.
     */
    public function handle(WhatsAppWebhookProcessor $processor): void
    {
        if ($this->event->processed_at) {
            return;
        }

        $this->event->increment('attempt_count');
        $this->event->update(['processing_started_at' => now(), 'failed_at' => null, 'processing_error' => null]);
        $processor->process($this->event);
        $this->event->update(['processed_at' => now()]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->event->update([
            'failed_at' => now(),
            'processing_error' => $exception ? str($exception->getMessage())->limit(500)->toString() : 'Processing failed.',
        ]);
    }
}
