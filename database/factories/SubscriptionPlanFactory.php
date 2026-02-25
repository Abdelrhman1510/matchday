<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement(['Basic', 'Pro', 'Enterprise']);
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 9999),
            'price' => fake()->randomElement([29.99, 59.99, 99.99]),
            'currency' => 'SAR',
            'features' => [
                'max_branches' => fake()->numberBetween(1, 10),
                'max_matches' => fake()->numberBetween(10, 100),
                'analytics' => fake()->boolean(),
            ],
            'max_bookings' => fake()->optional()->numberBetween(50, 500),
            'has_analytics' => fake()->boolean(),
            'has_branding' => fake()->boolean(),
            'has_priority_support' => fake()->boolean(),
            'is_active' => true,
            // Limit columns
            'max_branches' => fake()->numberBetween(3, 10),
            'max_matches_per_month' => fake()->numberBetween(10, 100),
            'max_bookings_per_month' => fake()->numberBetween(50, 500),
            'max_staff_members' => fake()->numberBetween(3, 20),
            'max_offers' => fake()->numberBetween(3, 20),
            // Feature flags
            'has_chat' => fake()->boolean(),
            'has_qr_scanner' => fake()->boolean(),
            'has_occupancy_tracking' => fake()->boolean(),
        ];
    }
}
