<?php

namespace Database\Factories;

use App\Models\ConnectedApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConnectedApplication>
 */
class ConnectedApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(2),
            'webhook_url' => 'https://example.test/api/internal/bwa/whatsapp/events',
            'request_signing_secret' => fake()->sha256(),
            'event_signing_secret' => fake()->sha256(),
            'enabled' => true,
        ];
    }
}
