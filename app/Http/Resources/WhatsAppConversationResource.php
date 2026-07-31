<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WhatsAppConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'contact_id' => $this->resource->whatsapp_contact_id,
            'product' => $this->resource->product_slug,
            'state' => $this->resource->state->value,
            'customer_service_window_expires_at' => $this->resource->customer_service_window_expires_at?->toIso8601String(),
            'last_incoming_message_at' => $this->resource->last_incoming_message_at?->toIso8601String(),
            'last_outgoing_message_at' => $this->resource->last_outgoing_message_at?->toIso8601String(),
        ];
    }
}
