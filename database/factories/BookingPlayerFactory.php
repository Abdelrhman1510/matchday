<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingPlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'user_id' => null,
            'name' => fake()->name(),
            'position' => fake()->randomElement(['Forward', 'Midfielder', 'Defender', 'Goalkeeper']),
            'jersey_number' => fake()->numberBetween(1, 99),
            'is_captain' => false,
        ];
    }
}
