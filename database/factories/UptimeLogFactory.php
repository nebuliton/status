<?php

namespace Database\Factories;

use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Models\UptimeLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UptimeLog>
 */
class UptimeLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'status' => fake()->randomElement(ServiceStatus::cases()),
            'recorded_at' => fake()->dateTimeBetween('-90 days', 'now'),
        ];
    }
}
