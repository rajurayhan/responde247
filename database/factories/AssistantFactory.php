<?php

namespace Database\Factories;

use App\Models\Assistant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assistant>
 */
class AssistantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Assistant',
            'user_id' => User::factory(),
            'vapi_assistant_id' => 'assistant_' . $this->faker->uuid(),
            'created_by' => User::factory(),
            'type' => $this->faker->randomElement(['demo', 'production']),
            'phone_number' => $this->faker->optional()->phoneNumber(),
            'webhook_url' => $this->faker->optional()->url(),
        ];
    }

    /**
     * Indicate that the assistant is a demo.
     */
    public function demo(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'demo',
        ]);
    }

    /**
     * Indicate that the assistant is production.
     */
    public function production(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'production',
        ]);
    }
}
