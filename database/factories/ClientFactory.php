<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'org_name' => fake()->company(),
            'logo_address' => fake()->optional()->imageUrl(200, 200, 'business', true),
            'company_email' => fake()->optional()->companyEmail(),
            'domain' => fake()->optional()->domainName(),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }

    /**
     * Indicate that the client has a logo.
     */
    public function withLogo(): static
    {
        return $this->state(fn (array $attributes) => [
            'logo_address' => fake()->imageUrl(200, 200, 'business', true),
        ]);
    }

    /**
     * Indicate that the client has complete information.
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'logo_address' => fake()->imageUrl(200, 200, 'business', true),
            'company_email' => fake()->companyEmail(),
            'domain' => fake()->domainName(),
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the client is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the client is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
