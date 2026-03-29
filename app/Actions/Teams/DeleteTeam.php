<?php

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteTeam
{
    /**
     * Delete a team and safely move affected users to another team.
     */
    public function handle(Team $team): void
    {
        DB::transaction(function () use ($team) {
            User::query()
                ->where('current_team_id', $team->id)
                ->each(function (User $user) use ($team): void {
                    $fallbackTeam = $user->personalTeam();

                    if ($fallbackTeam?->is($team)) {
                        $fallbackTeam = $user->fallbackTeam($team);
                    }

                    if ($fallbackTeam) {
                        $user->switchTeam($fallbackTeam);

                        return;
                    }

                    $user->forceFill(['current_team_id' => null])->save();
                });

            $team->invitations()->delete();
            $team->memberships()->delete();
            $team->delete();
        });
    }
}
