<?php

namespace App\Messaging;

use App\Models\WhatsAppMessage;

interface MetaWhatsAppClient
{
    /** @return array<string, mixed> */
    public function send(WhatsAppMessage $message, string $recipient): array;
}
