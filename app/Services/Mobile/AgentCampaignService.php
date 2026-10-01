<?php

namespace App\Services\Mobile;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AgentCampaignService
{
    public function __construct(protected AgentAccessService $access)
    {
    }

    public function list(User $user): array
    {
        $leadQuery = $this->access->leadQuery($user);
        $campaigns = $this->access->campaignQuery($user)
            ->with('source')
            ->orderBy('name')
            ->get();

        return $campaigns
            ->map(fn (Campaign $campaign) => $this->serialize($campaign, clone $leadQuery))
            ->sortByDesc('pending_leads')
            ->values()
            ->all();
    }

    public function nextCall(User $user, Campaign $campaign, ?int $afterId = null): array
    {
        $this->access->authorizeCampaign($user, $campaign);

        $query = $this->callableQuery($user, $campaign->id)
            ->with([
                'source',
                'campaign',
                'assignedUser',
                'stage',
                'activities.user',
                'notesList.user',
                'tasks.assignedUser',
                'tasks.creator',
                'tasks.lead',
            ])
            ->orderBy('id');

        if ($afterId) {
            $query->where('id', '>', $afterId);
        }

        $lead = $query->first();
        $remaining = $this->callableQuery($user, $campaign->id)->count();

        return [
            'campaign' => $this->serialize($campaign->loadMissing('source'), $this->access->leadQuery($user)),
            'lead' => $lead,
            'remaining' => $remaining,
        ];
    }

    protected function callableQuery(User $user, int $campaignId): Builder
    {
        return $this->access->leadQuery($user)
            ->where('campaign_id', $campaignId)
            ->whereIn('status', $this->access->pendingStatuses())
            ->whereNotNull('phone')
            ->where('phone', '!=', '');
    }

    protected function serialize(Campaign $campaign, Builder $leadQuery): array
    {
        $scoped = (clone $leadQuery)->where('campaign_id', $campaign->id);
        $total = (clone $scoped)->count();
        $pending = (clone $scoped)->whereIn('status', $this->access->pendingStatuses())->count();
        $callable = (clone $scoped)
            ->whereIn('status', $this->access->pendingStatuses())
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->count();

        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'status' => $campaign->status,
            'source' => $campaign->source ? [
                'id' => $campaign->source->id,
                'name' => $campaign->source->name,
                'slug' => $campaign->source->slug,
            ] : null,
            'total_leads' => $total,
            'pending_leads' => $pending,
            'callable_leads' => $callable,
        ];
    }
}
