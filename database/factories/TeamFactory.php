<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' FC',
            'short_name' => strtoupper(fake()->lexify('???')),
            'logo' => 'https://via.placeholder.com/150',
            'league' => fake()->randomElement(['Premier League', 'La Liga', 'Serie A', 'Bundesliga', 'Ligue 1']),
            'country' => fake()->randomElement(['England', 'Spain', 'Italy', 'Germany', 'France', 'Saudi Arabia']),
            'is_popular' => fake()->boolean(30),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_popular' => true,
        ]);
    }
}
