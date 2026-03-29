<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! User::query()->where('email', 'status@nebuliton.test')->exists()) {
            User::factory()->create([
                'name' => 'Nebuliton Admin',
                'email' => 'status@nebuliton.test',
            ]);
        }

        if (! User::query()->where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        $this->call(StatusPageSeeder::class);
    }
}
