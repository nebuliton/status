<?php

namespace Database\Factories;

use App\Enums\MaintenanceStatus;
use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Maintenance>
 */
class MaintenanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraphs(2, true),
            'scheduled_at' => fake()->dateTimeBetween('-2 days', '+10 days'),
            'status' => fake()->randomElement(MaintenanceStatus::cases()),
        ];
    }
}
