<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use App\Messaging\WhatsAppProviderManager;
use App\Models\WhatsAppMessage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bwa:whatsapp:test-send {recipient} {message}')]
#[Description('Explicitly send one real WhatsApp smoke-test message.')]
class TestWhatsAppSend extends Command
{
    public function handle(WhatsAppProviderManager $providers): int
    {
        if (! config('services.whatsapp.live_send_enabled')) {
            $this->error('Refusing to send because WHATSAPP_LIVE_SEND_ENABLED is false.');

            return self::FAILURE;
        }

        $this->warn('This will send a real WhatsApp message.');

        if (! $this->confirm('Continue?')) {
            return self::FAILURE;
        }

        $message = new WhatsAppMessage([
            'direction' => MessageDirection::Outbound,
            'message_type' => 'text',
            'status' => MessageStatus::Queued,
            'text_body_encrypted' => $this->argument('message'),
        ]);
        $provider = $providers->active();
        $provider->send($message, preg_replace('/\D+/', '', (string) $this->argument('recipient')) ?? '');
        $this->info('Smoke-test request accepted by '.$provider->name().'.');

        return self::SUCCESS;
    }
}
