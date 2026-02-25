<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatRoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'match_id' => \App\Models\GameMatch::factory(),
            'branch_id' => null,
            'type' => 'public',
            'is_active' => true,
            'viewers_count' => 0,
        ];
    }

    public function publicType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'public',
            'branch_id' => null,
        ]);
    }

    public function cafe(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cafe',
            'branch_id' => Branch::factory(),
        ]);
    }
}
