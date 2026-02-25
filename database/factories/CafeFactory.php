<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CafeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->company() . ' Cafe',
            'logo' => null,
            'description' => fake()->text(200),
            'phone' => fake()->numerify('+966##########'),
            'city' => fake()->randomElement(['Riyadh', 'Jeddah', 'Dammam', 'Mecca', 'Medina']),
            'is_premium' => fake()->boolean(20),
            'is_featured' => false,
            'avg_rating' => fake()->randomFloat(1, 3.0, 5.0),
            'total_reviews' => fake()->numberBetween(0, 100),
            'subscription_plan' => fake()->randomElement(['starter', 'pro', 'elite']),
        ];
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_premium' => true,
            'subscription_plan' => 'elite',
        ]);
    }
}
