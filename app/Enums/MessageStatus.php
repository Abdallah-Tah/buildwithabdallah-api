<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageStatus: string
{
    case Queued = 'queued';
    case Accepted = 'accepted';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
    case Received = 'received';

    public function rank(): int
    {
        return match ($this) {
            self::Queued => 0,
            self::Accepted, self::Received => 1,
            self::Sent => 2,
            self::Delivered => 3,
            self::Read => 4,
            self::Failed => 5,
        };
    }
}
