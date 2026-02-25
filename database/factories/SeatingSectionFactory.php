<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatingSectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->randomElement(['Section A', 'Section B', 'VIP Area', 'General Seating']),
            'type' => fake()->randomElement(['standard', 'vip', 'premium', 'main_screen']),
            'total_seats' => fake()->numberBetween(10, 50),
            'extra_cost' => fake()->randomFloat(2, 0, 50),
            'icon' => fake()->optional()->emoji(),
        ];
    }

    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'vip',
            'extra_cost' => 25.00,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'premium',
            'extra_cost' => 15.00,
        ]);
    }

    public function mainScreen(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'main_screen',
            'extra_cost' => 10.00,
        ]);
    }
}
