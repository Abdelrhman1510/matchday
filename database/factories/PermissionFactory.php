<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'permission' => fake()->randomElement([
                'view-bookings',
                'manage-matches',
                'view-analytics',
                'manage-seating',
                'manage-staff',
            ]),
        ];
    }
}
