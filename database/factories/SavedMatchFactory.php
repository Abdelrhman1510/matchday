<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\GameMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavedMatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'match_id' => GameMatch::factory(),
        ];
    }
}
