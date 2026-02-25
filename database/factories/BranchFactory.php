<?php

namespace Database\Factories;

use App\Models\Cafe;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cafe_id' => Cafe::factory(),
            'name' => fake()->streetName() . ' Branch',
            'address' => fake()->address(),
            'latitude' => fake()->latitude(21, 28),
            'longitude' => fake()->longitude(36, 56),
            'total_seats' => fake()->numberBetween(20, 200),
            'is_open' => true,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_open' => false,
        ]);
    }
}
