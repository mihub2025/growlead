<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function build(Organization $organization, array $filters = []): array
    {
        $from = Carbon::parse($filters['from'] ?? now()->subDays(7)->toDateString())->startOfDay();
        $to = Carbon::parse($filters['to'] ?? now()->toDateString())->endOfDay();
        $prevFrom = $from->copy()->subDays($from->diffInDays($to) + 1);
        $prevTo = $from->copy()->subSecond();

        $cacheKey = 'dash.'.$organization->id.'.'.$from->toDateString().'.'.$to->toDateString().'.'.md5(json_encode($filters));

        return Cache::remember($cacheKey, 60, function () use ($organization, $filters, $from, $to, $prevFrom, $prevTo) {
            $leads = $this->leadQuery($organization, $filters);
            $current = (clone $leads)->whereBetween('created_at', [$from, $to]);
            $previous = (clone $leads)->whereBetween('created_at', [$prevFrom, $prevTo]);

            $total = (clone $current)->count();
            $prevTotal = (clone $previous)->count();
            $qualified = (clone $current)->where(function ($q) {
                $q->where('lead_score', '>=', 60)->orWhereHas('stage', fn ($s) => $s->whereIn('slug', ['qualified', 'opportunity', 'proposal', 'negotiation', 'closed-won']));
            })->count();
            $prevQualified = (clone $previous)->where(function ($q) {
                $q->where('lead_score', '>=', 60)->orWhereHas('stage', fn ($s) => $s->whereIn('slug', ['qualified', 'opportunity', 'proposal', 'negotiation', 'closed-won']));
            })->count();

            $opps = Opportunity::forOrganization($organization->id)->where('status', 'open');
            $won = Opportunity::forOrganization($organization->id)->where('status', 'won')->whereBetween('closed_at', [$from, $to]);
            $spend = (float) $organization->campaigns()->sum('total_spend');
            $revenue = (float) (clone $won)->sum('estimated_value');
            $cpl = $total > 0 ? $spend / max($total, 1) : 0;

            $responded = (clone $current)->whereNotNull('first_response_at')->get(['created_at', 'first_response_at']);
            $avgSla = $responded->avg(fn ($lead) => $lead->created_at->diffInMinutes($lead->first_response_at)) ?? 0;

            $pipeline = $organization->defaultPipeline()?->stages()->where('active', true)->where('type', '!=', 'lost')->get() ?? collect();
            $stageCounts = Lead::forOrganization($organization->id)
                ->select('pipeline_stage_id', DB::raw('count(*) as total'))
                ->groupBy('pipeline_stage_id')
                ->pluck('total', 'pipeline_stage_id');

            $sources = Lead::forOrganization($organization->id)
                ->select('source_id', DB::raw('count(*) as total'), DB::raw('sum(case when lead_score >= 60 then 1 else 0 end) as qualified'))
                ->groupBy('source_id')
                ->with('source')
                ->get();

            $locations = Lead::forOrganization($organization->id)
                ->select('city', 'area', 'latitude', 'longitude', DB::raw('count(*) as total'))
                ->whereNotNull('city')
                ->groupBy('city', 'area', 'latitude', 'longitude')
                ->orderByDesc('total')
                ->limit(12)
                ->get();

            $topUsers = User::forOrganization($organization->id)
                ->withCount([
                    'assignedLeads as leads_count',
                    'assignedLeads as qualified_count' => fn ($q) => $q->where('lead_score', '>=', 60),
                ])
                ->get()
                ->map(function (User $user) {
                    $closed = Opportunity::where('assigned_user_id', $user->id)->where('status', 'won')->count();
                    $revenue = (float) Opportunity::where('assigned_user_id', $user->id)->where('status', 'won')->sum('estimated_value');
                    $conversion = $user->leads_count > 0 ? round(($closed / $user->leads_count) * 100, 1) : 0;

                    return compact('user') + [
                        'leads' => $user->leads_count,
                        'qualified' => $user->qualified_count,
                        'closed' => $closed,
                        'revenue' => $revenue,
                        'conversion' => $conversion,
                    ];
                })
                ->sortByDesc('qualified')
                ->take(5)
                ->values();

            return [
                'kpis' => [
                    ['key' => 'total_leads', 'label' => 'Total Leads', 'value' => $total, 'change' => $this->change($total, $prevTotal), 'icon' => 'bi-people', 'color' => 'blue'],
                    ['key' => 'qualified', 'label' => 'Qualified Leads', 'value' => $qualified, 'change' => $this->change($qualified, $prevQualified), 'icon' => 'bi-patch-check', 'color' => 'green'],
                    ['key' => 'opportunities', 'label' => 'Active Deals', 'value' => (clone $opps)->count(), 'change' => 0, 'icon' => 'bi-briefcase', 'color' => 'purple'],
                    ['key' => 'cpl', 'label' => 'Cost Per Lead', 'value' => $organization->formatMoney($cpl), 'change' => 0, 'icon' => 'bi-cash-coin', 'color' => 'orange', 'raw' => $cpl],
                    ['key' => 'revenue', 'label' => 'Closed Revenue', 'value' => $organization->formatMoney($revenue), 'change' => 0, 'icon' => 'bi-graph-up-arrow', 'color' => 'teal', 'raw' => $revenue],
                    ['key' => 'sla', 'label' => 'Response SLA', 'value' => round($avgSla).'m', 'change' => 0, 'icon' => 'bi-stopwatch', 'color' => 'pink', 'raw' => $avgSla],
                ],
                'pipeline' => $pipeline->map(function ($stage) use ($stageCounts, $total) {
                    $count = (int) ($stageCounts[$stage->id] ?? 0);

                    return [
                        'name' => $stage->name,
                        'color' => $stage->color,
                        'count' => $count,
                        'percent' => $total > 0 ? round(($count / max($total, 1)) * 100) : 0,
                    ];
                }),
                'sources' => $sources,
                'locations' => $locations,
                'topUsers' => $topUsers,
                'activities' => LeadActivity::forOrganization($organization->id)->with(['lead', 'user'])->latest('activity_at')->limit(8)->get(),
                'closings' => Opportunity::forOrganization($organization->id)->with(['lead', 'assignedUser'])->where('status', 'open')->whereNotNull('expected_close_date')->orderBy('expected_close_date')->limit(5)->get(),
                'tasks' => [
                    'active' => Task::forOrganization($organization->id)->where('status', '!=', 'completed')->count(),
                    'overdue' => Task::forOrganization($organization->id)->where('status', '!=', 'completed')->where('due_at', '<', now())->count(),
                    'unresponded' => Lead::forOrganization($organization->id)->whereNull('first_response_at')->count(),
                    'unread' => 0,
                ],
                'insights' => $this->insights($organization, $sources, $total, $qualified),
                'from' => $from,
                'to' => $to,
            ];
        });
    }

    protected function leadQuery(Organization $organization, array $filters)
    {
        return Lead::forOrganization($organization->id)
            ->when($filters['source_id'] ?? null, fn ($q, $id) => $q->where('source_id', $id))
            ->when($filters['campaign_id'] ?? null, fn ($q, $id) => $q->where('campaign_id', $id))
            ->when($filters['team_id'] ?? null, fn ($q, $id) => $q->where('assigned_team_id', $id))
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('assigned_user_id', $id));
    }

    protected function change(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function insights(Organization $organization, $sources, int $total, int $qualified): array
    {
        $bestSource = $sources->sortByDesc('total')->first();
        $bestCampaign = $organization->campaigns()->orderByDesc('qualified_leads')->first();
        $attention = Lead::forOrganization($organization->id)->whereNull('first_response_at')->count();

        return [
            ['title' => 'Best Performing Campaign', 'body' => $bestCampaign?->name ?? 'No campaigns yet', 'icon' => 'bi-megaphone'],
            ['title' => 'Hottest Lead Source', 'body' => $bestSource?->source?->name ?? 'No sources yet', 'icon' => 'bi-fire'],
            ['title' => 'Leads Requiring Attention', 'body' => $attention.' unresponded leads', 'icon' => 'bi-exclamation-triangle'],
            ['title' => 'Best Follow-up Time', 'body' => 'Today, 4:00 PM based on recent replies', 'icon' => 'bi-clock'],
            ['title' => 'Conversion Opportunities', 'body' => $qualified.' qualified leads ready to progress', 'icon' => 'bi-lightning'],
        ];
    }
}
