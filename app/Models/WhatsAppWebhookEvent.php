<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppWebhookEvent extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'whatsapp_webhook_events';

    protected $fillable = ['provider', 'payload_hash', 'object_type', 'event_type', 'raw_payload', 'received_at', 'processing_started_at', 'processed_at', 'failed_at', 'attempt_count', 'processing_error'];

    protected $attributes = ['attempt_count' => 0];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'received_at' => 'datetime',
            'processing_started_at' => 'datetime',
            'processed_at' => 'datetime',
            'failed_at' => 'datetime',
            'attempt_count' => 'integer',
        ];
    }
}
