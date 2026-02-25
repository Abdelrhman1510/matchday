<?php

namespace Database\Factories;

use App\Models\SeatingSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'section_id' => SeatingSection::factory(),
            'label' => fake()->bothify('?-##'),
            'is_available' => true,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }
}
