<?php

namespace App\Models;

use App\Enums\ConversationState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppConversation extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'whatsapp_conversations';

    protected $fillable = ['whatsapp_contact_id', 'connected_application_id', 'product_slug', 'state', 'customer_service_window_started_at', 'customer_service_window_expires_at', 'last_incoming_message_at', 'last_outgoing_message_at', 'routed_at', 'closed_at', 'metadata'];

    protected $attributes = ['state' => 'new'];

    protected function casts(): array
    {
        return [
            'state' => ConversationState::class,
            'customer_service_window_started_at' => 'datetime',
            'customer_service_window_expires_at' => 'datetime',
            'last_incoming_message_at' => 'datetime',
            'last_outgoing_message_at' => 'datetime',
            'routed_at' => 'datetime',
            'closed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(WhatsAppContact::class, 'whatsapp_contact_id');
    }

    public function connectedApplication(): BelongsTo
    {
        return $this->belongsTo(ConnectedApplication::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class);
    }
}
