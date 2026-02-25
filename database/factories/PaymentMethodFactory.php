<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentMethodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['credit_card', 'debit_card', 'wallet', 'bank_transfer']),
            'card_last_four' => fake()->numerify('####'),
            'card_holder' => fake()->name(),
            'expires_at' => fake()->creditCardExpirationDateString(),
            'is_primary' => false,
            'provider_token' => 'tok_' . fake()->uuid(),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
