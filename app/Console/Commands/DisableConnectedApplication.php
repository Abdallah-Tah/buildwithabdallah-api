<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ConnectedApplication;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bwa:application:disable {slug}')]
#[Description('Disable a connected application.')]
class DisableConnectedApplication extends Command
{
    public function handle(): int
    {
        $application = ConnectedApplication::query()->where('slug', $this->argument('slug'))->firstOrFail();
        $application->update(['enabled' => false]);
        $this->info('Application disabled.');

        return self::SUCCESS;
    }
}
