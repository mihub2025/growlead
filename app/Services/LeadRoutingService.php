<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;

class LeadRoutingService
{
    public function assign(Lead $lead, ?Campaign $campaign = null): ?User
    {
        $campaign ??= $lead->campaign;
        $method = $campaign?->routing_method ?? 'round_robin';
        $users = $this->eligibleUsers($lead, $campaign);

        if ($users->isEmpty()) {
            return null;
        }

        $user = match ($method) {
            'least_assigned' => $this->leastAssigned($users),
            'performance' => $this->performanceBased($users),
            'location' => $this->locationBased($users, $lead),
            'weighted' => $this->weighted($users, $campaign),
            default => $this->roundRobin($users, $campaign),
        };

        if ($user) {
            $lead->forceFill(['assigned_user_id' => $user->id])->save();
        }

        return $user;
    }

    protected function eligibleUsers(Lead $lead, ?Campaign $campaign)
    {
        if ($campaign && $campaign->users()->exists()) {
            return $campaign->users()->where('users.status', 'active')->get();
        }

        return User::forOrganization($lead->organization_id)->where('status', 'active')->get();
    }

    protected function roundRobin($users, ?Campaign $campaign): ?User
    {
        $lastId = $campaign?->settings['last_assigned_user_id'] ?? null;
        $sorted = $users->sortBy('id')->values();
        $next = $sorted->first(fn (User $user) => $user->id > (int) $lastId) ?? $sorted->first();

        if ($campaign && $next) {
            $settings = $campaign->settings ?? [];
            $settings['last_assigned_user_id'] = $next->id;
            $campaign->update(['settings' => $settings]);
        }

        return $next;
    }

    protected function leastAssigned($users): ?User
    {
        return $users->sortBy(fn (User $user) => $user->assignedLeads()->count())->first();
    }

    protected function performanceBased($users): ?User
    {
        return $users->sortByDesc(function (User $user) {
            $total = max(1, $user->assignedLeads()->count());
            $won = $user->assignedLeads()->where('status', 'won')->count();

            return $won / $total;
        })->first();
    }

    protected function locationBased($users, Lead $lead): ?User
    {
        if ($lead->city) {
            $match = $users->first(function (User $user) use ($lead) {
                return $user->teams->contains(fn ($team) => str_contains(strtolower($team->name), strtolower($lead->city)));
            });
            if ($match) {
                return $match;
            }
        }

        return $this->leastAssigned($users);
    }

    protected function weighted($users, ?Campaign $campaign): ?User
    {
        $weights = [];
        foreach ($users as $user) {
            $weights[$user->id] = (int) ($user->pivot->weight ?? 1);
        }
        $total = max(1, array_sum($weights));
        $pick = random_int(1, $total);
        $running = 0;
        foreach ($users as $user) {
            $running += $weights[$user->id] ?? 1;
            if ($pick <= $running) {
                return $user;
            }
        }

        return $users->first();
    }
}
