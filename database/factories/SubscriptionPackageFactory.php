<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPackage>
 */
class SubscriptionPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 9.99, 99.99),
            'voice_agents_limit' => $this->faker->numberBetween(1, 10),
            'monthly_minutes_limit' => $this->faker->numberBetween(100, 1000),
            'extra_per_minute_charge' => $this->faker->randomFloat(4, 0.01, 0.50),
            'features' => [
                'voice_assistants' => $this->faker->numberBetween(1, 5),
                'phone_numbers' => $this->faker->numberBetween(1, 3),
                'analytics' => $this->faker->boolean(),
                'priority_support' => $this->faker->boolean(),
            ],
            'support_level' => $this->faker->randomElement(['email', 'priority', 'dedicated']),
            'analytics_level' => $this->faker->randomElement(['basic', 'advanced', 'custom']),
            'is_popular' => $this->faker->boolean(20),
            'is_active' => true,
        ];
    }
}
