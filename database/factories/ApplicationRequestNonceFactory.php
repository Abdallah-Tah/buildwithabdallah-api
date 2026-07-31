<?php

namespace Database\Factories;

use App\Models\ApplicationRequestNonce;
use App\Models\ConnectedApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationRequestNonce>
 */
class ApplicationRequestNonceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'connected_application_id' => ConnectedApplication::factory(),
            'request_id' => fake()->uuid(),
            'timestamp' => now(),
            'body_hash' => fake()->sha256(),
            'expires_at' => now()->addMinutes(5),
        ];
    }
}
