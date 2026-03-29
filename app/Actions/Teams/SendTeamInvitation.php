<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SendTeamInvitation
{
    /**
     * Create an invitation and send the e-mail immediately.
     *
     * @param  array{email: string, role: string, team_id: int|string, expires_at?: mixed}  $data
     */
    public function handle(array $data, User $inviter): TeamInvitation
    {
        $team = Team::query()->findOrFail($data['team_id']);
        $email = Str::lower(trim($data['email']));

        $this->ensureCanInvite($team, $email);

        $invitation = $team->invitations()->create([
            'email' => $email,
            'role' => TeamRole::from($data['role']),
            'invited_by' => $inviter->id,
            'expires_at' => $data['expires_at'] ?? now()->addDays(7),
        ]);

        $this->send($invitation);

        return $invitation->fresh();
    }

    /**
     * Resend an existing invitation.
     */
    public function resend(TeamInvitation $invitation): void
    {
        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages([
                'invitation' => 'Diese Einladung wurde bereits angenommen.',
            ]);
        }

        if ($invitation->isExpired()) {
            $invitation->update([
                'expires_at' => now()->addDays(7),
            ]);
        }

        $this->send($invitation->fresh());
    }

    protected function send(TeamInvitation $invitation): void
    {
        Notification::route('mail', $invitation->email)
            ->notifyNow(new TeamInvitationNotification($invitation));
    }

    protected function ensureCanInvite(Team $team, string $email): void
    {
        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($existingUser?->belongsToTeam($team)) {
            throw ValidationException::withMessages([
                'email' => 'Diese E-Mail-Adresse gehört bereits zu einem Mitglied dieses Teams.',
            ]);
        }

        $hasPendingInvitation = TeamInvitation::query()
            ->where('team_id', $team->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(function ($query): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($hasPendingInvitation) {
            throw ValidationException::withMessages([
                'email' => 'Für diese E-Mail-Adresse gibt es bereits eine offene Einladung.',
            ]);
        }
    }
}
