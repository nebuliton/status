<?php

namespace App\Actions\Teams;

use App\Models\User;

class CreateTeam
{
    /**
     * Create a new team and add the user as owner.
     */
    public function handle(User $user, string $name, bool $isPersonal = false): \App\Models\Team
    {
        return app(CreateTeamWithOwner::class)->handle(
            owner: $user,
            name: $name,
            isPersonal: $isPersonal,
            switchOwnerToTeam: true,
        );
    }
}
