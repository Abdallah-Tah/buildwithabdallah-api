<?php

namespace Database\Factories;

use App\Models\WhatsAppContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppContact>
 */
class WhatsAppContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wa_id_hash' => hash('sha256', fake()->unique()->numerify('1207#######')),
            'wa_id_encrypted' => fake()->numerify('1207#######'),
            'phone_number_hash' => hash('sha256', fake()->unique()->numerify('1207#######')),
            'phone_number_encrypted' => fake()->numerify('1207#######'),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ];
    }
}
