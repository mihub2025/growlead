<?php

namespace App\Services\Mobile;

use App\Models\LeadActivity;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AgentDashboardService
{
    public function __construct(
        protected AgentAccessService $access,
        protected AgentCampaignService $campaigns
    ) {
    }

    public function build(User $user): array
    {
        $leads = $this->access->leadQuery($user);
        $tasks = $this->access->taskQuery($user)->incomplete();
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $newLeads = (clone $leads)->where(function ($q) {
            $q->whereIn('status', config('mobile.new_lead_statuses', []))
                ->orWhereHas('stage', fn ($s) => $s->whereIn('slug', ['new-lead', 'new']));
        })->count();

        $followUpsDue = (clone $tasks)->whereBetween('due_at', [$todayStart, $todayEnd])->count();
        $overdueTasks = (clone $tasks)->whereNotNull('due_at')->where('due_at', '<', now())->count();
        $qualifiedToday = (clone $leads)->whereIn('status', config('mobile.qualified_statuses', []))
            ->whereDate('updated_at', now()->toDateString())
            ->count();

        $todaysTasks = $this->access->taskQuery($user)
            ->incomplete()
            ->whereBetween('due_at', [$todayStart->copy()->subDay(), $todayEnd])
            ->orderByRaw('case when due_at < ? then 0 else 1 end', [now()])
            ->orderBy('due_at')
            ->limit(8)
            ->get();

        $needsAttention = $this->access->leadQuery($user)
            ->with(['source', 'stage', 'campaign'])
            ->orderByRaw('case when next_followup_at is not null and next_followup_at < ? then 0 else 1 end', [now()])
            ->orderByDesc('last_activity_at')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $monthStart = now()->startOfMonth();
        $totalLeads = (clone $leads)->count();
        $pendingLeads = (clone $leads)->whereIn('status', $this->access->pendingStatuses())->count();
        $closedLeads = (clone $leads)->whereIn('status', config('mobile.closed_statuses', []))->count();

        return [
            'greeting' => $this->greeting(),
            'agent' => [
                'id' => $user->id,
                'name' => $user->name,
                'initials' => $user->initials(),
                'role' => $user->roleName(),
                'organization' => $user->organization?->name,
            ],
            'metrics' => [
                ['key' => 'new_leads', 'label' => 'New Leads', 'value' => $newLeads, 'tone' => 'neutral'],
                ['key' => 'follow_ups_due', 'label' => 'Follow-ups Due', 'value' => $followUpsDue, 'tone' => $followUpsDue > 0 ? 'danger' : 'neutral'],
                ['key' => 'overdue_tasks', 'label' => 'Overdue Tasks', 'value' => $overdueTasks, 'tone' => $overdueTasks > 0 ? 'warning' : 'neutral'],
                ['key' => 'qualified_today', 'label' => 'Qualified Today', 'value' => $qualifiedToday, 'tone' => 'success'],
            ],
            'today_tasks' => $todaysTasks,
            'needs_attention' => $needsAttention,
            'stats' => [
                'leads_assigned' => $totalLeads,
                'activities_this_month' => LeadActivity::query()
                    ->forOrganization($user->organization_id)
                    ->where('user_id', $user->id)
                    ->where('activity_at', '>=', $monthStart)
                    ->count(),
                'tasks_completed' => Task::query()
                    ->forOrganization($user->organization_id)
                    ->where('assigned_user_id', $user->id)
                    ->where('status', 'completed')
                    ->where('completed_at', '>=', $monthStart)
                    ->count(),
            ],
            'pipeline' => [
                'summary' => [
                    ['key' => 'total', 'label' => 'Total Leads', 'value' => $totalLeads],
                    ['key' => 'contacted', 'label' => 'Contacted Leads', 'value' => max(0, $totalLeads - $pendingLeads)],
                    ['key' => 'pending', 'label' => 'Pending Leads', 'value' => $pendingLeads],
                    ['key' => 'closed', 'label' => 'Deals Closed', 'value' => $closedLeads],
                ],
                'statuses' => $this->pipelineRows($leads),
                'sources' => $this->sourceRows($leads),
            ],
            'productivity' => $this->productivity($user, $monthStart),
            'campaigns' => array_slice($this->campaigns->list($user), 0, 5),
        ];
    }

    protected function pipelineRows(Builder $leads): array
    {
        $rows = [];
        foreach (config('mobile.pipeline_rows', []) as $row) {
            $statuses = config('mobile.'.$row['statuses'], []);
            $rows[] = [
                'key' => $row['key'],
                'label' => $row['label'],
                'value' => $statuses ? (clone $leads)->whereIn('status', $statuses)->count() : 0,
            ];
        }

        return $rows;
    }

    protected function sourceRows(Builder $leads): array
    {
        $counts = (clone $leads)
            ->selectRaw('source_id, count(*) as total')
            ->whereNotNull('source_id')
            ->groupBy('source_id')
            ->pluck('total', 'source_id');

        if ($counts->isEmpty()) {
            return [];
        }

        $max = max(1, (int) $counts->max());
        $sources = \App\Models\LeadSource::query()
            ->whereIn('id', $counts->keys())
            ->orderBy('name')
            ->get();

        return $sources->map(fn ($source) => [
            'id' => $source->id,
            'name' => $source->name,
            'slug' => $source->slug,
            'value' => (int) $counts->get($source->id, 0),
            'share' => round(((int) $counts->get($source->id, 0) / $max) * 100),
        ])->sortByDesc('value')->values()->all();
    }

    protected function productivity(User $user, $monthStart): array
    {
        $activities = LeadActivity::query()
            ->forOrganization($user->organization_id)
            ->where('user_id', $user->id)
            ->where('activity_at', '>=', $monthStart);

        $calls = (clone $activities)->where('type', 'call');
        $callsMade = (clone $calls)->count();

        return [
            'period' => 'this_month',
            'channels' => [
                ['key' => 'calls', 'label' => 'Calls Made', 'value' => $callsMade],
                ['key' => 'whatsapp', 'label' => 'Whatsapps Sent', 'value' => (clone $activities)->where('type', 'whatsapp')->count()],
                ['key' => 'email', 'label' => 'Emails Sent', 'value' => (clone $activities)->where('type', 'email')->count()],
                ['key' => 'texts', 'label' => 'Texts Sent', 'value' => (clone $activities)->whereIn('type', ['sms', 'text'])->count()],
            ],
            'call_outcomes' => [
                ['key' => 'answered', 'label' => 'Answered', 'value' => $this->countOutcomes($calls, config('mobile.answered_outcomes', []))],
                ['key' => 'no_answer', 'label' => 'Did not respond', 'value' => $this->countOutcomes($calls, config('mobile.no_answer_outcomes', []))],
                ['key' => 'dead', 'label' => 'Dead number', 'value' => $this->countOutcomes($calls, config('mobile.dead_number_outcomes', []))],
            ],
            'calls_made' => $callsMade,
        ];
    }

    protected function countOutcomes(Builder $calls, array $outcomes): int
    {
        if (! $outcomes) {
            return 0;
        }

        return (clone $calls)->where(function (Builder $query) use ($outcomes) {
            foreach ($outcomes as $index => $outcome) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $query->{$method}('metadata->outcome', $outcome);
            }
        })->count();
    }

    protected function greeting(): string
    {
        $hour = (int) now()->format('G');
        if ($hour < 12) {
            return 'Good morning';
        }
        if ($hour < 17) {
            return 'Good afternoon';
        }

        return 'Good evening';
    }
}
