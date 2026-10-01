<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('campaigns.view');
    }

    public function view(User $user, Campaign $campaign): bool
    {
        if ($user->organization_id !== $campaign->organization_id || ! $user->hasPermission('campaigns.view')) {
            return false;
        }

        $access = app(\App\Services\Mobile\AgentAccessService::class);
        if (! $access->isScopedAgent($user)) {
            return true;
        }

        return in_array((int) $campaign->id, $access->assignedCampaignIds($user), true)
            || $access->leadQuery($user)->where('campaign_id', $campaign->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('campaigns.create');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->organization_id === $campaign->organization_id && $user->hasPermission('campaigns.edit');
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->organization_id === $campaign->organization_id && $user->hasPermission('campaigns.delete');
    }
}
