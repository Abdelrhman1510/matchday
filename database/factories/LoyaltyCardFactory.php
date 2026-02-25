<?php

namespace Database\Factories;

use App\Models\LoyaltyCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoyaltyCard>
 */
class LoyaltyCardFactory extends Factory
{
    protected $model = LoyaltyCard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $points = fake()->numberBetween(0, 100);
        return [
            'user_id' => User::factory(),
            'card_number' => 'MD' . date('Y') . str_pad(fake()->unique()->numberBetween(0, 9999999), 7, '0', STR_PAD_LEFT),
            'points' => $points,
            'total_points_earned' => $points,
            'tier' => 'bronze',
            'issued_date' => now(),
        ];
    }

    /**
     * Configure the factory to sync total_points_earned with points
     */
    public function configure(): static
    {
        return $this->afterMaking(function (LoyaltyCard $card) {
            // If points is set but total_points_earned is not, sync them
            if (!isset($card->total_points_earned) || $card->total_points_earned === 0) {
                $card->total_points_earned = $card->points;
            }
        });
    }

    /**
     * Indicate that the loyalty card is silver tier.
     */
    public function silver(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier' => 'silver',
            'points' => 1000,
            'total_points_earned' => 1000,
        ]);
    }

    /**
     * Indicate that the loyalty card is gold tier.
     */
    public function gold(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier' => 'gold',
            'points' => 5000,
            'total_points_earned' => 5000,
        ]);
    }

    /**
     * Indicate that the loyalty card is platinum tier.
     */
    public function platinum(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier' => 'platinum',
            'points' => 10000,
            'total_points_earned' => 10000,
        ]);
    }
}
