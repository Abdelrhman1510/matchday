<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserAchievementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'achievement_id' => Achievement::factory(),
            'unlocked_at' => now(),
        ];
    }
}
