<?php

namespace Database\Factories;

use App\Models\LoyaltyCard;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'loyalty_card_id' => LoyaltyCard::factory(),
            'booking_id' => null,
            'points' => fake()->numberBetween(10, 100),
            'type' => fake()->randomElement(['earned', 'redeemed']),
            'description' => fake()->sentence(),
        ];
    }

    public function earned(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earned',
            'description' => 'Points earned from booking',
        ]);
    }

    public function redeemed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'redeemed',
            'description' => 'Points redeemed',
        ]);
    }

    public function withBooking(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_id' => Booking::factory(),
        ]);
    }
}
