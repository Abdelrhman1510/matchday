<?php

namespace Database\Factories;

use App\Models\Cafe;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = \App\Models\CafeSubscription::class;

    public function definition(): array
    {
        return [
            'cafe_id' => Cafe::factory(),
            'plan_id' => SubscriptionPlan::factory(),
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'payment_method_id' => null,
            'auto_renew' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);
    }
}
