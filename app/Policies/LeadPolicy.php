<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leads.view');
    }

    public function view(User $user, Lead $lead): bool
    {
        if ($user->organization_id !== $lead->organization_id || ! $user->hasPermission('leads.view')) {
            return false;
        }

        return app(\App\Services\Mobile\AgentAccessService::class)->canAccessLead($user, $lead);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->organization_id === $lead->organization_id && $user->hasPermission('leads.edit');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->organization_id === $lead->organization_id && $user->hasPermission('leads.delete');
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $user->organization_id === $lead->organization_id && $user->hasPermission('leads.assign');
    }
}
