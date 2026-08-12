<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WhatsAppMessageResource extends JsonResource
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
            'status' => $this->resource->status->value,
            'correlation_id' => $this->resource->correlation_id,
            'idempotency_key' => $this->resource->idempotency_key,
            'direction' => $this->resource->direction->value,
            'type' => $this->resource->message_type,
            'provider' => $this->resource->provider,
            'provider_message_id' => $this->resource->provider_message_id,
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
