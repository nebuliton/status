<?php

namespace Database\Factories;

use App\Enums\ServiceCheckType;
use App\Enums\ServiceIconSource;
use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Models\ServiceGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = sprintf('%s %s', fake()->unique()->company(), fake()->randomElement(['API', 'Edge', 'Gateway', 'Worker', 'Portal']));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => fake()->randomElement(ServiceStatus::cases()),
            'uptime_percentage' => fake()->randomFloat(2, 96, 100),
            'group_id' => ServiceGroup::factory(),
            'check_type' => ServiceCheckType::Website,
            'icon_source' => ServiceIconSource::Auto,
            'check_enabled' => false,
            'check_interval_seconds' => 60,
            'timeout_seconds' => 5,
            'target_url' => 'https://example.com',
            'verify_ssl' => true,
            'latency_degraded_ms' => 700,
            'latency_down_ms' => 2000,
        ];
    }
}
