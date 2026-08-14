<?php

namespace App\Jobs;

use App\Messaging\ApplicationEventDispatcher;
use App\Models\ApplicationEventDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class DispatchApplicationEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public array $backoff = [1, 5, 30, 120];

    public function __construct(public ApplicationEventDelivery $delivery) {}

    /**
     * Execute the job.
     */
    public function handle(ApplicationEventDispatcher $dispatcher): void
    {
        if ($this->delivery->delivered_at) {
            return;
        }

        $application = $this->delivery->connectedApplication;

        if (! $application->enabled || ! $application->webhookUrlFor($this->delivery->event_type)) {
            $this->delivery->update(['status' => 'failed', 'last_error' => 'Application delivery is not configured.']);

            return;
        }

        $this->delivery->increment('attempt_count');
        $response = $dispatcher->dispatch($this->delivery);

        if (in_array($response->status(), [200, 202], true)) {
            $this->delivery->update(['status' => 'delivered', 'response_status' => $response->status(), 'delivered_at' => now(), 'last_error' => null]);
            $application->update(['last_event_delivered_at' => now()]);
            Log::info('application.event.dispatched', ['event_id' => $this->delivery->event_id, 'connected_application_slug' => $application->slug]);

            return;
        }

        $this->delivery->update(['status' => 'failed', 'response_status' => $response->status(), 'last_error' => 'Application returned HTTP '.$response->status().'.']);

        if ($response->serverError() || $response->status() === 429) {
            throw new RuntimeException('Transient application delivery failure.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->delivery->update(['status' => 'failed', 'last_error' => 'Application event delivery failed.']);
        Log::error('application.event.failed', ['event_id' => $this->delivery->event_id]);
    }
}
