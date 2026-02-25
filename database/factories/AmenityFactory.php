<?php

namespace Database\Factories;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Factories\Factory;

class AmenityFactory extends Factory
{
    protected $model = Amenity::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['WiFi', 'Parking', 'TV Screen', 'Air Conditioning', 'Outdoor Seating']),
            'icon' => fake()->optional()->emoji(),
        ];
    }
}
