<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Cafe;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavedCafeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'cafe_id' => Cafe::factory(),
        ];
    }
}
