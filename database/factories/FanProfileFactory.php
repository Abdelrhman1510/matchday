<?php

namespace Database\Factories;

use App\Models\FanProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FanProfile>
 */
class FanProfileFactory extends Factory
{
    protected $model = FanProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'favorite_team_id' => null,
            'matches_attended' => 0,
            'member_since' => now(),
        ];
    }

    /**
     * Indicate that the fan profile has a favorite team.
     */
    public function withFavoriteTeam(): static
    {
        return $this->state(fn (array $attributes) => [
            'favorite_team_id' => \App\Models\Team::factory(),
        ]);
    }
}
