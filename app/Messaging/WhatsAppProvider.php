<?php

namespace App\Messaging;

use App\Models\WhatsAppMessage;

interface WhatsAppProvider
{
    public function name(): string;

    public function send(WhatsAppMessage $message, string $recipient): ProviderSendResult;
}
