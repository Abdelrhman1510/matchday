<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AchievementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'icon' => fake()->emoji(),
            'criteria_type' => fake()->randomElement(['booking_count', 'match_attended', 'points_earned', 'loyalty_tier']),
            'criteria_value' => fake()->numberBetween(1, 10),
            'points_reward' => fake()->numberBetween(10, 100),
            'requirement' => fake()->sentence(3),
            'category' => fake()->randomElement(['bookings', 'matches', 'loyalty', 'social']),
        ];
    }
}
