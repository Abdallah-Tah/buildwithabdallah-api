<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ConnectedApplication;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('bwa:application:rotate-secret {slug} {--type=both : request, event, or both}')]
#[Description('Rotate signing secrets for a connected application.')]
class RotateConnectedApplicationSecret extends Command
{
    public function handle(): int
    {
        $application = ConnectedApplication::query()->where('slug', $this->argument('slug'))->firstOrFail();
        $type = (string) $this->option('type');

        if (! in_array($type, ['request', 'event', 'both'], true)) {
            $this->error('Type must be request, event, or both.');

            return self::FAILURE;
        }

        $changes = [];

        if (in_array($type, ['request', 'both'], true)) {
            $changes['request_signing_secret'] = Str::random(64);
        }

        if (in_array($type, ['event', 'both'], true)) {
            $changes['event_signing_secret'] = Str::random(64);
        }

        $application->update($changes);
        $this->warn('Store the rotated secrets now. They will not be shown again.');

        foreach ($changes as $name => $secret) {
            $this->line(Str::headline($name).': '.$secret);
        }

        return self::SUCCESS;
    }
}
