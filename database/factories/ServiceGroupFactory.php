<?php

namespace Database\Factories;

use App\Models\ServiceGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceGroup>
 */
class ServiceGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Infrastructure',
                'API',
                'Frontend',
                'Authentication',
                'Observability',
            ]),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
