<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasPermission('teams.manage');
    }

    public function update(User $user, Team $team): bool
    {
        return $user->organization_id === $team->organization_id && $user->hasPermission('teams.manage');
    }
}
