<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ConnectedApplication;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('bwa:application:create {--name=} {--slug=} {--webhook-url=} {--enabled=1}')]
#[Description('Create a connected Build With Abdallah application.')]
class CreateConnectedApplication extends Command
{
    public function handle(): int
    {
        $name = (string) ($this->option('name') ?: $this->ask('Name'));
        $slug = Str::slug((string) ($this->option('slug') ?: $this->ask('Slug')));
        $webhookUrl = (string) ($this->option('webhook-url') ?: $this->ask('Webhook URL (optional)', ''));
        $requestSecret = Str::random(64);
        $eventSecret = Str::random(64);

        ConnectedApplication::create([
            'name' => $name,
            'slug' => $slug,
            'webhook_url' => $webhookUrl !== '' ? $webhookUrl : null,
            'request_signing_secret' => $requestSecret,
            'event_signing_secret' => $eventSecret,
            'enabled' => filter_var($this->option('enabled'), FILTER_VALIDATE_BOOL),
        ]);

        $this->warn('Store these secrets now. They will not be shown again.');
        $this->line('Request signing secret: '.$requestSecret);
        $this->line('Event signing secret: '.$eventSecret);

        return self::SUCCESS;
    }
}
