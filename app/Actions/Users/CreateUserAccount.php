<?php

namespace App\Actions\Users;

use App\Actions\Teams\CreateTeamWithOwner;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUserAccount
{
    /**
     * Create a user account with a personal team.
     *
     * @param  array{name: string, email: string, password: string, email_verified_at?: mixed, is_admin?: mixed}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'email_verified_at' => $data['email_verified_at'] ?? now(),
                'is_admin' => (bool) ($data['is_admin'] ?? false),
            ]);

            app(CreateTeamWithOwner::class)->handle(
                owner: $user,
                name: "{$user->name} Team",
                isPersonal: true,
                switchOwnerToTeam: true,
            );

            return $user->fresh(['currentTeam']);
        });
    }
}
