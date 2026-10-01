<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\PipelineStage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function forTab(Organization $organization, array $filters = [], string $tab = 'executive'): array
    {
        $data = $this->executive($organization, $filters);

        return match ($tab) {
            'sales' => array_merge($data, $this->sales($organization, $filters, $data)),
            'marketing' => array_merge($data, $this->marketing($organization, $filters, $data)),
            'users' => array_merge($data, $this->agents($organization, $filters, $data)),
            'pipeline' => array_merge($data, $this->pipeline($organization, $filters, $data)),
            'custom' => array_merge($data, $this->custom($organization, $filters)),
            default => $data,
        };
    }

    public function executive(Organization $organization, array $filters = []): array
    {
        [$from, $to] = $this->range($filters);

        $leads = $this->leadQuery($organization, $filters)->whereBetween('created_at', [$from, $to]);

        $total = (clone $leads)->count();
        $qualified = (clone $leads)->where('lead_score', '>=', 60)->count();
        $wonOpps = Opportunity::forOrganization($organization->id)->where('status', 'won')->whereBetween('closed_at', [$from, $to]);
        $revenue = (float) (clone $wonOpps)->sum('estimated_value');
        $wonCount = (clone $wonOpps)->count();
        $spend = (float) $organization->campaigns()->sum('total_spend');
        $openOpps = Opportunity::forOrganization($organization->id)->where('status', 'open');
        $forecast = (float) (clone $openOpps)->get()->sum(fn ($o) => $o->forecastValue());

        $responded = (clone $leads)->whereNotNull('first_response_at')->get(['created_at', 'first_response_at']);
        $avgResponse = $responded->avg(fn ($lead) => $lead->created_at->diffInMinutes($lead->first_response_at)) ?? 0;

        $funnel = $organization->defaultPipeline()?->stages()->where('active', true)->get()->map(function ($stage) use ($organization) {
            $count = Lead::forOrganization($organization->id)->where('pipeline_stage_id', $stage->id)->count();

            return ['name' => $stage->name, 'count' => $count, 'color' => $stage->color];
        }) ?? collect();

        $sourceRoi = $this->leadQuery($organization, $filters)
            ->select('source_id', DB::raw('count(*) as leads'), DB::raw('sum(case when lead_score >= 60 then 1 else 0 end) as qualified'))
            ->groupBy('source_id')
            ->with('source')
            ->get();

        $campaigns = $organization->campaigns()->with('source')->orderByDesc('total_leads')->limit(12)->get();

        $users = User::forOrganization($organization->id)->get()->map(function (User $user) use ($from, $to) {
            $assigned = $user->assignedLeads()->whereBetween('created_at', [$from, $to])->count();
            $closed = Opportunity::where('assigned_user_id', $user->id)->where('status', 'won')->whereBetween('closed_at', [$from, $to])->count();
            $revenue = (float) Opportunity::where('assigned_user_id', $user->id)->where('status', 'won')->whereBetween('closed_at', [$from, $to])->sum('estimated_value');
            $responded = $user->assignedLeads()->whereNotNull('first_response_at')->whereBetween('created_at', [$from, $to])->get(['created_at', 'first_response_at']);
            $avgResponse = $responded->avg(fn ($lead) => $lead->created_at->diffInMinutes($lead->first_response_at)) ?? 0;

            return [
                'user' => $user,
                'assigned' => $assigned,
                'closed' => $closed,
                'revenue' => $revenue,
                'response' => round($avgResponse),
                'conversion' => $assigned ? round(($closed / $assigned) * 100, 1) : 0,
            ];
        })->sortByDesc('revenue')->values();

        $stalled = (clone $openOpps)->where(function ($q) {
            $q->where('expected_close_date', '<', now())->orWhereNull('expected_close_date');
        })->count();
        $atRisk = (clone $openOpps)->where('probability', '<', 40)->count();
        $healthy = max(0, (clone $openOpps)->count() - $stalled - $atRisk);

        return [
            'kpis' => [
                ['label' => 'Total Revenue', 'value' => $organization->formatMoney($revenue), 'icon' => 'bi-currency-dollar', 'color' => 'blue'],
                ['label' => 'Qualified Rate', 'value' => ($total ? round(($qualified / $total) * 100, 1) : 0).'%', 'icon' => 'bi-patch-check', 'color' => 'green'],
                ['label' => 'Cost Per Qualified Lead', 'value' => $organization->formatMoney($qualified ? $spend / max($qualified, 1) : 0), 'icon' => 'bi-tag', 'color' => 'purple'],
                ['label' => 'Avg. Response Time', 'value' => round($avgResponse).'m', 'icon' => 'bi-stopwatch', 'color' => 'yellow'],
                ['label' => 'Show-up Rate', 'value' => ($total ? round(($wonCount / max($total, 1)) * 100, 1) : 0).'%', 'icon' => 'bi-graph-up', 'color' => 'teal'],
                ['label' => 'Forecasted Closings', 'value' => $organization->formatMoney($forecast), 'icon' => 'bi-calendar-check', 'color' => 'pink'],
            ],
            'totals' => compact('total', 'qualified', 'revenue', 'spend', 'wonCount', 'avgResponse'),
            'funnel' => $funnel,
            'sourceRoi' => $sourceRoi,
            'campaigns' => $campaigns,
            'users' => $users,
            'locations' => Lead::forOrganization($organization->id)->select('city', DB::raw('count(*) as total'))->whereNotNull('city')->groupBy('city')->orderByDesc('total')->limit(8)->get(),
            'pipelineHealth' => [
                'healthy' => $healthy,
                'at_risk' => $atRisk,
                'stalled' => $stalled,
                'won' => $wonCount,
                'open' => (clone $openOpps)->count(),
            ],
            'forecast' => [
                'revenue' => $forecast,
                'deals' => (clone $openOpps)->count(),
            ],
            'responseTrend' => $this->fillSeries($from, $to, $responded->groupBy(fn ($lead) => $lead->created_at->toDateString())->map(
                fn ($rows) => round($rows->avg(fn ($lead) => $lead->created_at->diffInMinutes($lead->first_response_at)) ?? 0)
            )),
            'leadTrend' => $this->fillSeries($from, $to, $this->leadQuery($organization, $filters)
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('date(created_at) as d, count(*) as total')
                ->groupByRaw('date(created_at)')
                ->pluck('total', 'd')),
        ];
    }

    private function sales(Organization $organization, array $filters, array $base): array
    {
        [$from, $to] = $this->range($filters);
        $won = Opportunity::forOrganization($organization->id)->where('status', 'won')->whereBetween('closed_at', [$from, $to]);
        $lost = Opportunity::forOrganization($organization->id)->where('status', 'lost')->whereBetween('closed_at', [$from, $to]);
        $open = Opportunity::forOrganization($organization->id)->where('status', 'open');
        $wonCount = (clone $won)->count();
        $lostCount = (clone $lost)->count();
        $openCount = (clone $open)->count();
        $wonValue = (float) (clone $won)->sum('estimated_value');
        $lostValue = (float) (clone $lost)->sum('estimated_value');
        $openValue = (float) (clone $open)->sum('estimated_value');
        $closed = max(1, $wonCount + $lostCount);
        $avgDeal = $wonCount ? $wonValue / $wonCount : 0;

        $byDay = Opportunity::forOrganization($organization->id)
            ->where('status', 'won')
            ->whereBetween('closed_at', [$from, $to])
            ->selectRaw('date(closed_at) as d, sum(estimated_value) as revenue, count(*) as deals')
            ->groupByRaw('date(closed_at)')
            ->get()
            ->keyBy('d');

        $deals = Opportunity::forOrganization($organization->id)
            ->with(['lead', 'assignedUser', 'stage'])
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('closed_at', [$from, $to])->orWhere(function ($open) use ($from, $to) {
                    $open->where('status', 'open')->whereBetween('created_at', [$from, $to]);
                });
            })
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();

        return [
            'kpis' => [
                ['label' => 'Won Revenue', 'value' => $organization->formatMoney($wonValue), 'icon' => 'bi-currency-dollar'],
                ['label' => 'Won Deals', 'value' => number_format($wonCount), 'icon' => 'bi-trophy'],
                ['label' => 'Avg. Deal Size', 'value' => $organization->formatMoney($avgDeal), 'icon' => 'bi-tag'],
                ['label' => 'Win Rate', 'value' => round(($wonCount / $closed) * 100, 1).'%', 'icon' => 'bi-graph-up-arrow'],
                ['label' => 'Open Pipeline', 'value' => $organization->formatMoney($openValue), 'icon' => 'bi-kanban'],
                ['label' => 'Forecast', 'value' => $organization->formatMoney($base['forecast']['revenue'] ?? 0), 'icon' => 'bi-calendar-check'],
            ],
            'salesMix' => [
                'won' => $wonCount,
                'lost' => $lostCount,
                'open' => $openCount,
                'won_value' => $wonValue,
                'lost_value' => $lostValue,
                'open_value' => $openValue,
            ],
            'revenueTrend' => $this->fillSeries($from, $to, $byDay->map(fn ($row) => (float) $row->revenue)),
            'dealsTrend' => $this->fillSeries($from, $to, $byDay->map(fn ($row) => (int) $row->deals)),
            'deals' => $deals,
        ];
    }

    private function marketing(Organization $organization, array $filters, array $base): array
    {
        $campaigns = $organization->campaigns()->with('source')->orderByDesc('total_leads')->get();
        $spend = (float) $campaigns->sum('total_spend');
        $leads = (int) ($base['totals']['total'] ?? 0);
        $qualified = (int) ($base['totals']['qualified'] ?? 0);
        $revenue = (float) ($base['totals']['revenue'] ?? 0);
        $active = $campaigns->where('status', 'active')->count();

        return [
            'kpis' => [
                ['label' => 'Total Spend', 'value' => $organization->formatMoney($spend), 'icon' => 'bi-cash-coin'],
                ['label' => 'Leads', 'value' => number_format($leads), 'icon' => 'bi-people'],
                ['label' => 'Qualified', 'value' => number_format($qualified), 'icon' => 'bi-patch-check'],
                ['label' => 'CPL', 'value' => $organization->formatMoney($leads ? $spend / max($leads, 1) : 0), 'icon' => 'bi-tag'],
                ['label' => 'ROAS', 'value' => ($spend ? round($revenue / $spend, 2) : 0).'x', 'icon' => 'bi-graph-up-arrow'],
                ['label' => 'Active Campaigns', 'value' => number_format($active), 'icon' => 'bi-megaphone'],
            ],
            'campaignRows' => $campaigns,
        ];
    }

    private function agents(Organization $organization, array $filters, array $base): array
    {
        $users = collect($base['users'] ?? []);

        return [
            'kpis' => [
                ['label' => 'Agents', 'value' => number_format($users->count()), 'icon' => 'bi-people'],
                ['label' => 'Leads Assigned', 'value' => number_format($users->sum('assigned')), 'icon' => 'bi-person-check'],
                ['label' => 'Deals Won', 'value' => number_format($users->sum('closed')), 'icon' => 'bi-trophy'],
                ['label' => 'Team Revenue', 'value' => $organization->formatMoney($users->sum('revenue')), 'icon' => 'bi-currency-dollar'],
                ['label' => 'Avg. Conversion', 'value' => round($users->avg('conversion') ?? 0, 1).'%', 'icon' => 'bi-graph-up'],
                ['label' => 'Avg. Response', 'value' => round($users->avg('response') ?? 0).'m', 'icon' => 'bi-stopwatch'],
            ],
        ];
    }

    private function pipeline(Organization $organization, array $filters, array $base): array
    {
        $open = Opportunity::forOrganization($organization->id)->where('status', 'open')->with(['lead', 'assignedUser', 'stage']);
        $stalledDeals = (clone $open)->where(function ($q) {
            $q->where('expected_close_date', '<', now())->orWhereNull('expected_close_date');
        })->orderBy('expected_close_date')->limit(8)->get();
        $riskDeals = (clone $open)->where('probability', '<', 40)->orderBy('probability')->limit(8)->get();

        $stageValue = Opportunity::forOrganization($organization->id)
            ->where('status', 'open')
            ->select('pipeline_stage_id', DB::raw('count(*) as deals'), DB::raw('sum(estimated_value) as value'))
            ->groupBy('pipeline_stage_id')
            ->get()
            ->keyBy('pipeline_stage_id');

        $stages = $organization->defaultPipeline()?->stages()->where('active', true)->get()->map(function (PipelineStage $stage) use ($base, $stageValue) {
            $funnel = collect($base['funnel'] ?? [])->firstWhere('name', $stage->name);

            return [
                'name' => $stage->name,
                'color' => $stage->color,
                'leads' => $funnel['count'] ?? 0,
                'deals' => (int) ($stageValue[$stage->id]->deals ?? 0),
                'value' => (float) ($stageValue[$stage->id]->value ?? 0),
            ];
        }) ?? collect();

        return [
            'kpis' => [
                ['label' => 'Open Deals', 'value' => number_format($base['pipelineHealth']['open'] ?? 0), 'icon' => 'bi-kanban'],
                ['label' => 'Healthy', 'value' => number_format($base['pipelineHealth']['healthy'] ?? 0), 'icon' => 'bi-heart'],
                ['label' => 'At Risk', 'value' => number_format($base['pipelineHealth']['at_risk'] ?? 0), 'icon' => 'bi-exclamation-triangle'],
                ['label' => 'Stalled', 'value' => number_format($base['pipelineHealth']['stalled'] ?? 0), 'icon' => 'bi-pause-circle'],
                ['label' => 'Won in Period', 'value' => number_format($base['pipelineHealth']['won'] ?? 0), 'icon' => 'bi-trophy'],
                ['label' => 'Forecast', 'value' => $organization->formatMoney($base['forecast']['revenue'] ?? 0), 'icon' => 'bi-calendar-check'],
            ],
            'stageRows' => $stages,
            'stalledDeals' => $stalledDeals,
            'riskDeals' => $riskDeals,
        ];
    }

    private function custom(Organization $organization, array $filters): array
    {
        [$from, $to] = $this->range($filters);
        $group = $filters['group_by'] ?? 'source';
        $allowed = ['source', 'campaign', 'agent', 'city', 'stage', 'day'];
        if (! in_array($group, $allowed, true)) {
            $group = 'source';
        }

        $expr = match ($group) {
            'source' => 'source_id',
            'campaign' => 'campaign_id',
            'agent' => 'assigned_user_id',
            'city' => 'city',
            'stage' => 'pipeline_stage_id',
            'day' => 'date(created_at)',
        };

        $rows = $this->leadQuery($organization, $filters)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw($expr.' as grp, count(*) as leads, sum(case when lead_score >= 60 then 1 else 0 end) as qualified')
            ->groupByRaw($expr)
            ->orderByDesc('leads')
            ->limit(40)
            ->get();

        $names = $this->groupNames($organization, $group, $rows->pluck('grp'));

        $customRows = $rows->map(fn ($row) => [
            'label' => $names[$row->grp] ?? ($row->grp ?: 'Unassigned'),
            'leads' => (int) $row->leads,
            'qualified' => (int) $row->qualified,
            'rate' => $row->leads ? round(($row->qualified / $row->leads) * 100, 1) : 0,
        ]);

        return [
            'groupBy' => $group,
            'customRows' => $customRows,
        ];
    }

    private function groupNames(Organization $organization, string $group, $ids)
    {
        return match ($group) {
            'source' => $organization->leadSources()->whereIn('id', $ids)->pluck('name', 'id'),
            'campaign' => $organization->campaigns()->whereIn('id', $ids)->pluck('name', 'id'),
            'agent' => $organization->users()->whereIn('id', $ids)->pluck('name', 'id'),
            'stage' => PipelineStage::whereIn('id', $ids)->pluck('name', 'id'),
            default => collect(),
        };
    }

    private function leadQuery(Organization $organization, array $filters)
    {
        return Lead::forOrganization($organization->id)
            ->when($filters['source_id'] ?? null, fn ($q, $id) => $q->where('source_id', $id))
            ->when($filters['campaign_id'] ?? null, fn ($q, $id) => $q->where('campaign_id', $id))
            ->when($filters['team_id'] ?? null, fn ($q, $id) => $q->where('assigned_team_id', $id))
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('assigned_user_id', $id))
            ->when($filters['interested_in'] ?? null, fn ($q, $val) => $q->where('interested_in', $val));
    }

    private function range(array $filters): array
    {
        $from = Carbon::parse($filters['from'] ?? now()->subDays(7)->toDateString())->startOfDay();
        $to = Carbon::parse($filters['to'] ?? now()->toDateString())->endOfDay();

        return [$from, $to];
    }

    private function fillSeries(Carbon $from, Carbon $to, $keyed): array
    {
        $map = collect($keyed);
        $labels = [];
        $values = [];
        $days = max(1, $from->diffInDays($to));
        if ($days <= 14) {
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $labels[] = $d->format('j M');
                $values[] = (float) ($map[$d->toDateString()] ?? 0);
            }
        } else {
            $cursor = $from->copy()->startOfWeek();
            while ($cursor->lte($to)) {
                $end = $cursor->copy()->endOfWeek();
                $labels[] = $cursor->format('j M');
                $sum = 0;
                for ($d = $cursor->copy(); $d->lte($end) && $d->lte($to); $d->addDay()) {
                    $sum += (float) ($map[$d->toDateString()] ?? 0);
                }
                $values[] = $sum;
                $cursor->addWeek();
            }
        }

        return compact('labels', 'values');
    }
}
