<?php

namespace Database\Factories;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'excerpt' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'is_pinned' => false,
            'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
