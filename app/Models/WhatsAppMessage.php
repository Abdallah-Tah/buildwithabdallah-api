<?php

namespace App\Models;

use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'whatsapp_messages';

    protected $fillable = ['whatsapp_conversation_id', 'whatsapp_contact_id', 'connected_application_id', 'meta_message_id', 'correlation_id', 'idempotency_key', 'request_hash', 'direction', 'message_type', 'status', 'text_body_encrypted', 'template_name', 'template_language', 'media_id', 'reply_to_meta_message_id', 'request_payload', 'response_payload', 'failure_code', 'failure_message', 'sent_at', 'delivered_at', 'read_at', 'failed_at'];

    protected $hidden = ['text_body_encrypted', 'request_payload', 'response_payload'];

    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
            'status' => MessageStatus::class,
            'text_body_encrypted' => 'encrypted',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'whatsapp_conversation_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(WhatsAppContact::class, 'whatsapp_contact_id');
    }

    public function connectedApplication(): BelongsTo
    {
        return $this->belongsTo(ConnectedApplication::class);
    }
}
