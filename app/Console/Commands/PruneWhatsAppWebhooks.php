<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WhatsAppWebhookEvent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('bwa:whatsapp:prune-webhooks {--dry-run}')]
#[Description('Redact expired webhook payloads and delete expired temporary Sent media.')]
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

        $mediaDisk = Storage::disk((string) config('services.sent_dm.media_disk', 'local'));
        $mediaCutoff = now()->subHours((int) config('services.sent_dm.media_retention_hours', 48))->timestamp;
        $expiredMedia = collect($mediaDisk->files('sent-whatsapp-documents'))
            ->filter(fn (string $path): bool => $mediaDisk->lastModified($path) < $mediaCutoff);

        if (! $this->option('dry-run') && $expiredMedia->isNotEmpty()) {
            $mediaDisk->delete($expiredMedia->all());
        }

        $this->info(($this->option('dry-run') ? 'Would redact ' : 'Redacted ').$count.' webhook payload(s).');
        $this->info(($this->option('dry-run') ? 'Would delete ' : 'Deleted ').$expiredMedia->count().' temporary media file(s).');

        return self::SUCCESS;
    }
}
