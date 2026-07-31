<?php

namespace Database\Factories;

use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppMessage>
 */
class WhatsAppMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'whatsapp_conversation_id' => WhatsAppConversation::factory(),
            'whatsapp_contact_id' => WhatsAppContact::factory(),
            'direction' => 'inbound',
            'message_type' => 'text',
            'status' => 'received',
            'text_body_encrypted' => fake()->sentence(),
        ];
    }
}
