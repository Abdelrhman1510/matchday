<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\ChatRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_id' => ChatRoom::factory(),
            'user_id' => User::factory(),
            'message' => fake()->sentence(),
            'type' => 'text',
        ];
    }

    public function emoji(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'emoji',
            'message' => fake()->randomElement(['👍', '❤️', '😂', '🎉', '⚽']),
        ]);
    }
}
