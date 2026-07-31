<?php

namespace Database\Factories;

use App\Models\ApplicationEventDelivery;
use App\Models\ConnectedApplication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApplicationEventDelivery>
 */
class ApplicationEventDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => (string) Str::ulid(),
            'connected_application_id' => ConnectedApplication::factory(),
            'event_type' => 'whatsapp.message.received',
            'payload' => ['event_type' => 'whatsapp.message.received'],
            'status' => 'pending',
        ];
    }
}
