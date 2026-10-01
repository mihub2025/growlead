<?php

namespace App\Policies;

use App\Models\AutomationRule;
use App\Models\User;

class AutomationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('automations.view');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermission('automations.manage');
    }

    public function update(User $user, AutomationRule $rule): bool
    {
        return $user->organization_id === $rule->organization_id && $user->hasPermission('automations.manage');
    }
}
