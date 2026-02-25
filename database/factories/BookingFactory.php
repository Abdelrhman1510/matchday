<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\GameMatch;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomElement([100, 150, 200, 250, 300]);
        $serviceFee = $subtotal * 0.10; // 10% service fee
        $totalAmount = $subtotal + $serviceFee;

        return [
            'user_id' => User::factory(),
            'match_id' => GameMatch::factory(),
            'branch_id' => Branch::factory(),
            'booking_code' => 'BOOK-' . strtoupper(Str::random(8)),
            'guests_count' => fake()->numberBetween(1, 6),
            'status' => 'confirmed',
            'special_requests' => fake()->optional()->sentence(),
            'subtotal' => $subtotal,
            'service_fee' => $serviceFee,
            'total_amount' => $totalAmount,
            'currency' => 'SAR',
            'qr_code' => Str::uuid(),
            'checked_in_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function checked_in(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);
    }
}
