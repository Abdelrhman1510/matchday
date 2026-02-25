<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameMatchFactory extends Factory
{
    protected $model = \App\Models\GameMatch::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'league' => fake()->randomElement(['Premier League', 'La Liga', 'Serie A', 'Bundesliga']),
            'match_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'kick_off' => fake()->time('H:i'),
            'status' => 'upcoming',
            'home_score' => null,
            'away_score' => null,
            'seats_available' => fake()->numberBetween(20, 100),
            'price_per_seat' => fake()->randomElement([50, 75, 100, 150, 200]),
            'duration_minutes' => 90,
            'total_revenue' => 0,
            'is_published' => true,
            'is_trending' => false,
            'booking_opens_at' => now()->subDays(7),
            'booking_closes_at' => fake()->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'status' => 'upcoming',
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'status' => 'upcoming',
        ]);
    }

    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'status' => 'live',
        ]);
    }

    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'status' => 'finished',
            'home_score' => fake()->numberBetween(0, 5),
            'away_score' => fake()->numberBetween(0, 5),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'status' => 'cancelled',
        ]);
    }
}
