<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateOperationsAdmin extends Command
{
    protected $signature = 'ops:admin
        {email : The administrator email address}
        {--name= : The administrator display name}
        {--password= : A temporary password; generated when omitted}';

    protected $description = 'Create or promote a secured operations-panel administrator';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->components->error('Provide a valid email address.');

            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();
        $password = (string) ($this->option('password') ?: Str::password(24, symbols: true));
        $name = trim((string) ($this->option('name') ?: $existing?->name ?: Str::before($email, '@')));

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => $existing?->email_verified_at ?? now(),
                'is_admin' => true,
                'role' => 'super_admin',
            ],
        );

        $this->components->info($existing ? 'Operations administrator updated.' : 'Operations administrator created.');
        $this->line('Email: '.$user->email);
        $this->line('Temporary password: '.$password);
        $this->components->warn('Sign in, enroll app-based MFA, and replace the temporary password immediately.');

        return self::SUCCESS;
    }
}
