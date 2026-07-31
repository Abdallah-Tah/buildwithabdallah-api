<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ConnectedApplication;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bwa:application:list')]
#[Description('List connected applications without exposing secrets.')]
class ListConnectedApplications extends Command
{
    public function handle(): int
    {
        $this->table(
            ['Name', 'Slug', 'Enabled', 'Webhook URL'],
            ConnectedApplication::query()->oldest('name')->get()->map(fn (ConnectedApplication $application): array => [
                $application->name,
                $application->slug,
                $application->enabled ? 'yes' : 'no',
                $application->webhook_url ?? '—',
            ]),
        );

        return self::SUCCESS;
    }
}
