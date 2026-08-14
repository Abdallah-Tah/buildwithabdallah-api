<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class StripeWebhookEvent extends Model
{
    use HasUlids;

    protected $fillable = [
        'stripe_event_id',
        'type',
        'livemode',
        'payload',
        'status',
        'processed_at',
        'error_message',
    ];

    protected $hidden = ['payload'];

    protected function casts(): array
    {
        return [
            'livemode' => 'boolean',
            'payload' => 'encrypted',
            'processed_at' => 'datetime',
        ];
    }
}
