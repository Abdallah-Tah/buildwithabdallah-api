<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationEventDelivery extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['event_id', 'connected_application_id', 'whatsapp_message_id', 'event_type', 'payload', 'status', 'attempt_count', 'response_status', 'last_error', 'delivered_at'];

    protected $attributes = ['status' => 'pending', 'attempt_count' => 0];

    protected function casts(): array
    {
        return ['payload' => 'array', 'delivered_at' => 'datetime', 'attempt_count' => 'integer'];
    }

    public function connectedApplication(): BelongsTo
    {
        return $this->belongsTo(ConnectedApplication::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }
}
