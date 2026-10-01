<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;

class CampaignService
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function create(Organization $organization, array $data, ?User $actor = null): Campaign
    {
        $campaign = Campaign::create(array_merge($data, [
            'organization_id' => $organization->id,
            'created_by' => $actor?->id,
            'currency' => $organization->currencyCode(),
            'status' => $data['status'] ?? 'draft',
            'sync_status' => 'manual',
        ]));

        if (! empty($data['user_ids'])) {
            $sync = [];
            foreach ($data['user_ids'] as $id) {
                $sync[$id] = ['weight' => $data['weights'][$id] ?? 1];
            }
            $campaign->users()->sync($sync);
        }

        $this->audit->log('campaign.created', $campaign, null, $campaign->toArray(), $actor);

        return $campaign;
    }

    public function update(Campaign $campaign, array $data, ?User $actor = null): Campaign
    {
        $before = $campaign->toArray();
        $data['currency'] = $campaign->organization?->currencyCode() ?: $campaign->currency;
        $campaign->update($data);
        if (array_key_exists('user_ids', $data)) {
            $sync = [];
            foreach ($data['user_ids'] ?? [] as $id) {
                $sync[$id] = ['weight' => $data['weights'][$id] ?? 1];
            }
            $campaign->users()->sync($sync);
        }
        $this->audit->log('campaign.updated', $campaign, $before, $campaign->toArray(), $actor);

        return $campaign->fresh();
    }

    public function assignUsers(Campaign $campaign, array $userIds, ?User $actor = null): Campaign
    {
        $validIds = User::forOrganization($campaign->organization_id)
            ->where('status', 'active')
            ->whereIn('id', $userIds)
            ->pluck('id')
            ->all();

        $weights = $campaign->users()->pluck('weight', 'users.id');
        $sync = [];
        foreach ($validIds as $id) {
            $sync[$id] = ['weight' => (int) ($weights[$id] ?? 1)];
        }
        $campaign->users()->sync($sync);
        $this->audit->log('campaign.assigned', $campaign, null, ['user_ids' => $validIds], $actor);

        return $campaign->fresh('users');
    }

    public function refreshStats(Campaign $campaign): void
    {
        $leads = $campaign->leads();
        $campaign->update([
            'total_leads' => (clone $leads)->count(),
            'qualified_leads' => (clone $leads)->where('lead_score', '>=', 60)->count(),
            'conversions' => $campaign->leads()->whereHas('opportunities', fn ($q) => $q->where('status', 'won'))->count(),
            'revenue' => $campaign->leads()->join('opportunities', 'opportunities.lead_id', '=', 'leads.id')
                ->where('opportunities.status', 'won')->sum('opportunities.estimated_value'),
        ]);
    }
}
