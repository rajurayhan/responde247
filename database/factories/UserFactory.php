<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => fake()->randomElement(['user', 'admin']),
            'phone' => fake()->optional()->phoneNumber(),
            'company' => fake()->optional()->company(),
            'bio' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'reseller_id' => null, // Will be set when creating reseller-related users
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    /**
     * Create a reseller admin user.
     */
    public function resellerAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'reseller_admin',
            'status' => 'active',
        ]);
    }

    /**
     * Create a user with a specific reseller.
     */
    public function forClient($resellerId): static
    {
        return $this->state(fn (array $attributes) => [
            'reseller_id' => $resellerId,
        ]);
    }
}
