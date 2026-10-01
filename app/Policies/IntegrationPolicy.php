<?php

namespace App\Policies;

use App\Models\Integration;
use App\Models\User;

class IntegrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('integrations.view');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermission('integrations.manage');
    }

    public function update(User $user, Integration $integration): bool
    {
        return $user->organization_id === $integration->organization_id && $user->hasPermission('integrations.manage');
    }
}
