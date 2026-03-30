<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->where('email', 'test@example.com')->delete();

        User::query()->updateOrCreate(
            ['email' => 'status@nebuliton.test'],
            [
                'name' => 'Nebuliton Initial-Admin',
                'email_verified_at' => now(),
                'is_admin' => true,
                'password' => Hash::make('password'),
            ],
        );

        $this->call(StatusPageSeeder::class);

        $this->command?->warn('Es wurden bewusst keine Demo-Daten für Dienste, Vorfälle oder Wartungen angelegt.');
        $this->command?->warn('Initialer Admin: status@nebuliton.test / password');
        $this->command?->warn('Bitte diesen Initial-Admin nach dem Anlegen deiner echten Administratorkonten wieder löschen oder das Passwort sofort ändern.');
    }
}
