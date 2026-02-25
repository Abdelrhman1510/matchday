<?php

namespace Database\Factories;

use App\Models\Cafe;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cafe_id' => Cafe::factory(),
            'branch_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'image' => null,
            'original_price' => fake()->randomFloat(2, 20, 100),
            'offer_price' => fake()->randomFloat(2, 10, 50),
            'discount_percent' => fake()->numberBetween(10, 50),
            'discount_value' => fake()->numberBetween(10, 50),
            'discount' => fake()->numberBetween(10, 50),
            'discount_type' => fake()->randomElement(['percentage', 'bogo', 'free_item']),
            'type' => fake()->randomElement(['percentage', 'bogo', 'free_item']),
            'status' => 'active',
            'is_featured' => fake()->boolean(20),
            'is_active' => true,
            'valid_from' => now(),
            'valid_until' => now()->addDays(30),
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'available_for' => fake()->randomElement(['all', 'weekend', 'prime_time']),
            'terms' => fake()->optional()->paragraph(),
            'usage_count' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'valid_until' => now()->addDays(30),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'valid_until' => now()->subDay(),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
