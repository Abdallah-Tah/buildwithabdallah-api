<?php

declare(strict_types=1);

namespace App\Enums;

enum ConversationState: string
{
    case New = 'new';
    case AwaitingProductSelection = 'awaiting_product_selection';
    case Active = 'active';
    case Closed = 'closed';
    case Blocked = 'blocked';
}
