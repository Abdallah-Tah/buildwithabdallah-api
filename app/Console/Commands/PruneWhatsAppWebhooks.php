<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WhatsAppWebhookEvent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bwa:whatsapp:prune-webhooks {--dry-run}')]
#[Description('Redact expired raw Meta webhook payloads.')]
class PruneWhatsAppWebhooks extends Command
{
    public function handle(): int
    {
        $cutoff = now()->subDays((int) config('services.meta_whatsapp.webhook_retention_days', 30));
        $query = WhatsAppWebhookEvent::query()->where('received_at', '<', $cutoff)->whereNotNull('raw_payload');
        $count = $query->count();

        if (! $this->option('dry-run')) {
            $query->update(['raw_payload' => null]);
        }

        $this->info(($this->option('dry-run') ? 'Would redact ' : 'Redacted ').$count.' webhook payload(s).');

        return self::SUCCESS;
    }
}
