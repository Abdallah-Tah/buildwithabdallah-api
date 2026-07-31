<?php

namespace Database\Factories;

use App\Models\WhatsAppWebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppWebhookEvent>
 */
class WhatsAppWebhookEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payload_hash' => fake()->sha256(),
            'object_type' => 'whatsapp_business_account',
            'event_type' => 'message',
            'raw_payload' => [],
            'received_at' => now(),
        ];
    }
}
