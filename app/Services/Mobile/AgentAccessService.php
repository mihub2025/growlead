<?php

namespace App\Services\Mobile;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AgentAccessService
{
    public function isScopedAgent(User $user): bool
    {
        if ($user->isAdministrator() || $user->hasRole('manager') || $user->hasRole('team_lead')) {
            return false;
        }

        return $user->hasRole('agent') || $user->hasRole('viewer');
    }

    public function assignedCampaignIds(User $user): array
    {
        return $user->campaigns()->pluck('campaigns.id')->map(fn ($id) => (int) $id)->all();
    }

    public function leadQuery(User $user): Builder
    {
        $query = Lead::query()
            ->forOrganization($user->organization_id)
            ->with(['source', 'campaign', 'assignedUser', 'stage']);

        if ($this->isScopedAgent($user)) {
            $campaignIds = $this->assignedCampaignIds($user);
            $query->where(function (Builder $inner) use ($user, $campaignIds) {
                $inner->where('assigned_user_id', $user->id)
                    ->orWhere('co_assigned_user_id', $user->id);
                if ($campaignIds) {
                    $inner->orWhereIn('campaign_id', $campaignIds);
                }
            });
        }

        return $query;
    }

    public function campaignQuery(User $user): Builder
    {
        $query = Campaign::query()->forOrganization($user->organization_id);

        if ($this->isScopedAgent($user)) {
            $assigned = $this->assignedCampaignIds($user);
            $fromLeads = $this->leadQuery($user)
                ->whereNotNull('campaign_id')
                ->distinct()
                ->pluck('campaign_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $ids = array_values(array_unique(array_merge($assigned, $fromLeads)));
            $query->whereIn('id', $ids ?: [0]);
        }

        return $query;
    }

    public function taskQuery(User $user): Builder
    {
        $query = Task::query()->forOrganization($user->organization_id)->with(['lead', 'assignedUser', 'creator']);

        if ($this->isScopedAgent($user) || ! $user->isAdministrator()) {
            $query->where(function (Builder $inner) use ($user) {
                $inner->where('assigned_user_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        return $query;
    }

    public function canAccessLead(User $user, Lead $lead): bool
    {
        if ((int) $user->organization_id !== (int) $lead->organization_id) {
            return false;
        }

        if ($this->isScopedAgent($user)) {
            if ((int) $lead->assigned_user_id === (int) $user->id
                || (int) $lead->co_assigned_user_id === (int) $user->id) {
                return true;
            }

            return (bool) $lead->campaign_id
                && in_array((int) $lead->campaign_id, $this->assignedCampaignIds($user), true);
        }

        return $user->isAdministrator() || $user->hasPermission('leads.view');
    }

    public function authorizeLead(User $user, Lead $lead): Lead
    {
        if ((int) $user->organization_id !== (int) $lead->organization_id) {
            abort(404);
        }

        abort_unless($this->canAccessLead($user, $lead), 403, 'You do not have access to this lead.');

        return $lead;
    }

    public function authorizeTask(User $user, Task $task): Task
    {
        if ((int) $user->organization_id !== (int) $task->organization_id) {
            abort(404);
        }

        $owns = (int) $task->assigned_user_id === (int) $user->id
            || (int) $task->created_by === (int) $user->id
            || $user->isAdministrator();

        abort_unless($owns, 403, 'You do not have access to this task.');

        return $task;
    }

    public function authorizeCampaign(User $user, Campaign $campaign): Campaign
    {
        if ((int) $user->organization_id !== (int) $campaign->organization_id) {
            abort(404);
        }

        if ($this->isScopedAgent($user)) {
            $assigned = in_array((int) $campaign->id, $this->assignedCampaignIds($user), true);
            $hasLead = $this->leadQuery($user)->where('campaign_id', $campaign->id)->exists();
            abort_unless($assigned || $hasLead, 403, 'You do not have access to this campaign.');
        }

        return $campaign;
    }

    public function pendingStatuses(): array
    {
        return config('mobile.pending_statuses', []);
    }
}
