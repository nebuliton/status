<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTeamWithOwner
{
    /**
     * Create a new team and attach the selected owner.
     */
    public function handle(
        User $owner,
        string $name,
        bool $isPersonal = false,
        bool $switchOwnerToTeam = false,
    ): Team {
        return DB::transaction(function () use ($owner, $name, $isPersonal, $switchOwnerToTeam) {
            $team = Team::create([
                'name' => $name,
                'is_personal' => $isPersonal,
            ]);

            $team->memberships()->create([
                'user_id' => $owner->id,
                'role' => TeamRole::Owner,
            ]);

            if ($switchOwnerToTeam || blank($owner->current_team_id)) {
                $owner->switchTeam($team);
            }

            return $team->fresh();
        });
    }
}
