<?php

namespace Database\Factories;

use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppConversation>
 */
class WhatsAppConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'whatsapp_contact_id' => WhatsAppContact::factory(),
            'state' => 'awaiting_product_selection',
        ];
    }
}
