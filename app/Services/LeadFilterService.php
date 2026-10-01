<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Organization;
use App\Models\PipelineStage;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LeadFilterService
{
    public function apply(Builder $query, array $filters, User $user): Builder
    {
        $org = $user->organization;
        $tz = $org?->timezone ?: config('app.timezone');

        $this->applySearch($query, $filters['search'] ?? null);
        $this->applyStatus($query, Arr::wrap($filters['status'] ?? []), $org);
        $this->applyLabels($query, Arr::wrap($filters['labels'] ?? []));
        $this->applyCampaigns($query, Arr::wrap($filters['campaigns'] ?? []), (bool) ($filters['campaign_created_by_agent'] ?? false));
        $this->applyAgents($query, Arr::wrap($filters['agents'] ?? []), (bool) ($filters['include_unassigned'] ?? false));
        $this->applyRelativeTime($query, 'leads.updated_at', $filters, 'last_updated');
        $this->applyRelativeTime($query, 'leads.last_assigned_at', $filters, 'last_assigned');
        $this->applyDatePreset($query, 'leads.created_at', $filters['creation_date'] ?? null, $filters['creation_from'] ?? null, $filters['creation_to'] ?? null, $tz);
        $this->applyDatePreset($query, 'leads.meta_created_at', $filters['meta_creation_date'] ?? null, $filters['meta_creation_from'] ?? null, $filters['meta_creation_to'] ?? null, $tz);
        $this->applyDealValue($query, $filters['deal_value_from'] ?? null, $filters['deal_value_to'] ?? null, $filters['deal_value_applied'] ?? null);
        $this->applyTaskTypes($query, Arr::wrap($filters['task_types'] ?? []));
        $this->applyTaskStatus($query, Arr::wrap($filters['task_status'] ?? []));
        $this->applyCallsMade($query, $filters);
        $this->applySources($query, $filters);
        $this->applyTimesAssigned($query, $filters);

        if (! empty($filters['pipeline_stage_id'])) {
            $query->where('pipeline_stage_id', $filters['pipeline_stage_id']);
        }
        if (! empty($filters['assigned_team_id'])) {
            $query->where('assigned_team_id', $filters['assigned_team_id']);
        }
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (! empty($filters['city'])) {
            $query->where('city', 'like', '%'.$filters['city'].'%');
        }
        if (! empty($filters['interested_in'])) {
            $query->where('interested_in', 'like', '%'.$filters['interested_in'].'%');
        }
        if ($filters['min_score'] ?? null) {
            $query->where('lead_score', '>=', $filters['min_score']);
        }

        return $query;
    }

    public function options(Organization $org): array
    {
        $campaigns = Campaign::forOrganization($org->id)->with(['source', 'creator.roles'])->orderBy('name')->get();
        $agents = User::forOrganization($org->id)->with('roles')->orderBy('name')->get();
        $sources = LeadSource::query()
            ->where(function ($q) use ($org) {
                $q->where('organization_id', $org->id)->orWhereNull('organization_id');
            })
            ->orderBy('name')
            ->get();

        $taskTypeMap = collect(config('crm.default_task_types', []))->map(fn ($row) => [
            'slug' => $row['slug'],
            'name' => $row['name'],
        ]);
        $extraTypes = Task::forOrganization($org->id)->select('type')->distinct()->pluck('type')->filter();
        foreach ($extraTypes as $type) {
            if ($taskTypeMap->contains(fn ($row) => $row['slug'] === $type)) {
                continue;
            }
            $taskTypeMap->push([
                'slug' => $type,
                'name' => Str::headline(str_replace(['_', '-'], ' ', (string) $type)),
            ]);
        }

        return [
            'statuses' => $this->statusOptions($org),
            'tags' => Tag::forOrganization($org->id)->orderBy('name')->get(),
            'campaigns' => $campaigns,
            'agents' => $agents,
            'sources' => LeadSource::withoutDuplicateAliases($sources),
            'taskTypes' => $taskTypeMap->values()->all(),
        ];
    }

    public function statusOptions(Organization $org): array
    {
        $items = [];
        $seen = [];
        $add = function (string $slug, string $name) use (&$items, &$seen) {
            $key = strtolower($slug);
            $nameKey = strtolower($name);
            if (isset($seen[$key]) || isset($seen[$nameKey])) {
                return;
            }
            $seen[$key] = true;
            $seen[$nameKey] = true;
            $items[] = ['slug' => $slug, 'name' => $name];
        };

        foreach (config('crm.default_lead_statuses', []) as $row) {
            $add($row['slug'], $row['name']);
        }

        $pipelineIds = $org->pipelines()->pluck('id');
        foreach (PipelineStage::whereIn('pipeline_id', $pipelineIds)->orderBy('position')->get() as $stage) {
            $add($stage->slug, $stage->name);
        }

        foreach (Lead::forOrganization($org->id)->whereNotNull('status')->distinct()->pluck('status') as $status) {
            $add((string) $status, Str::headline(str_replace(['_', '-'], ' ', (string) $status)));
        }

        return $items;
    }

    public function state(array $filters): array
    {
        $counts = [
            'status' => count(Arr::wrap($filters['status'] ?? [])),
            'labels' => count(Arr::wrap($filters['labels'] ?? [])),
            'campaigns' => count(Arr::wrap($filters['campaigns'] ?? [])) + ((int) ($filters['campaign_created_by_agent'] ?? false)),
            'agents' => count(Arr::wrap($filters['agents'] ?? [])) + ((int) ($filters['include_unassigned'] ?? false)),
            'last_updated' => $this->relativeIsActive($filters, 'last_updated') ? 1 : 0,
            'last_assigned' => $this->relativeIsActive($filters, 'last_assigned') ? 1 : 0,
            'creation_date' => $this->dateIsActive($filters, 'creation') ? 1 : 0,
            'meta_creation_date' => $this->dateIsActive($filters, 'meta_creation') ? 1 : 0,
            'deal_value' => $this->dealIsActive($filters) ? 1 : 0,
            'task_types' => count(Arr::wrap($filters['task_types'] ?? [])),
            'task_status' => count(Arr::wrap($filters['task_status'] ?? [])),
            'calls' => $this->countIsActive($filters, 'calls') ? 1 : 0,
            'sources' => count(Arr::wrap($filters['sources'] ?? [])) + ($this->sourceAdvancedActive($filters) ? 1 : 0),
            'times_assigned' => $this->countIsActive($filters, 'times_assigned') ? 1 : 0,
        ];

        $hasActive = collect($counts)->sum() > 0
            || filled($filters['pipeline_stage_id'] ?? null)
            || filled($filters['assigned_team_id'] ?? null)
            || filled($filters['priority'] ?? null)
            || filled($filters['city'] ?? null)
            || filled($filters['interested_in'] ?? null)
            || filled($filters['min_score'] ?? null);

        return [
            'values' => $filters,
            'counts' => $counts,
            'hasActive' => $hasActive,
        ];
    }

    public function hasActiveFilters(array $filters): bool
    {
        return $this->state($filters)['hasActive'];
    }

    protected function applySearch(Builder $query, ?string $search): void
    {
        if (! filled($search)) {
            return;
        }

        $query->where(function (Builder $inner) use ($search) {
            $inner->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('whatsapp', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%")
                ->orWhere('interested_in', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%")
                ->orWhere('area', 'like', "%{$search}%");
        });
    }

    protected function applyStatus(Builder $query, array $selected, ?Organization $org): void
    {
        $selected = array_values(array_filter($selected, fn ($v) => $v !== '' && $v !== null));
        if (! $selected) {
            return;
        }

        $expanded = [];
        foreach (config('crm.default_lead_statuses', []) as $row) {
            foreach ($selected as $value) {
                if (strcasecmp((string) $row['slug'], (string) $value) === 0 || strcasecmp((string) $row['name'], (string) $value) === 0) {
                    $expanded[] = $row['slug'];
                    $expanded[] = $row['name'];
                    foreach ($row['aliases'] ?? [] as $alias) {
                        $expanded[] = $alias;
                    }
                }
            }
        }
        foreach ($selected as $value) {
            $expanded[] = $value;
        }
        $expanded = array_values(array_unique($expanded));

        $stageIds = collect();
        if ($org) {
            $stageIds = PipelineStage::whereIn('pipeline_id', $org->pipelines()->pluck('id'))
                ->where(function (Builder $q) use ($selected, $expanded) {
                    $q->whereIn('slug', $expanded)->orWhereIn('name', $expanded)->orWhereIn('slug', $selected)->orWhereIn('name', $selected);
                })
                ->pluck('id');
        }

        $query->where(function (Builder $q) use ($expanded, $stageIds) {
            $q->whereIn('leads.status', $expanded);
            if ($stageIds->isNotEmpty()) {
                $q->orWhereIn('leads.pipeline_stage_id', $stageIds->all());
            }
        });
    }

    protected function applyLabels(Builder $query, array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (! $ids) {
            return;
        }

        $query->whereHas('tags', fn (Builder $q) => $q->whereIn('tags.id', $ids));
    }

    protected function applyCampaigns(Builder $query, array $ids, bool $createdByAgent): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (! $ids && ! $createdByAgent) {
            return;
        }

        $query->where(function (Builder $q) use ($ids, $createdByAgent) {
            if ($ids) {
                $q->whereIn('campaign_id', $ids);
            }
            if ($createdByAgent) {
                $method = $ids ? 'orWhereHas' : 'whereHas';
                $q->{$method}('campaign.creator.roles', fn (Builder $roles) => $roles->where('slug', 'agent'));
            }
        });
    }

    protected function applyAgents(Builder $query, array $ids, bool $unassigned): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (! $ids && ! $unassigned) {
            return;
        }

        $query->where(function (Builder $q) use ($ids, $unassigned) {
            if ($ids) {
                $q->whereIn('assigned_user_id', $ids);
            }
            if ($unassigned) {
                $ids ? $q->orWhereNull('assigned_user_id') : $q->whereNull('assigned_user_id');
            }
        });
    }

    protected function applyRelativeTime(Builder $query, string $column, array $filters, string $prefix): void
    {
        if (! $this->relativeIsActive($filters, $prefix)) {
            return;
        }

        $operator = $filters[$prefix.'_operator'] ?? 'more';
        $value = (int) ($filters[$prefix.'_value'] ?? 0);
        $unit = $filters[$prefix.'_unit'] ?? 'hours';

        $threshold = match ($unit) {
            'minutes' => now()->subMinutes($value),
            'days' => now()->subDays($value),
            default => now()->subHours($value),
        };

        $query->whereNotNull($column);
        if ($operator === 'less') {
            $query->where($column, '>', $threshold);
        } else {
            $query->where($column, '<', $threshold);
        }
    }

    protected function applyDatePreset(Builder $query, string $column, ?string $preset, ?string $from, ?string $to, string $tz): void
    {
        if (! $preset && ! $from && ! $to) {
            return;
        }

        if ($preset === 'custom' || (! $preset && ($from || $to))) {
            if ($from) {
                $query->whereDate($column, '>=', $from);
            }
            if ($to) {
                $query->whereDate($column, '<=', $to);
            }

            return;
        }

        $now = Carbon::now($tz);
        $lastMonth = $now->copy()->subMonthNoOverflow();
        $lastQuarter = $now->copy()->subQuarter();
        [$start, $end] = match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last_7_days' => [$now->copy()->subDays(7)->startOfDay(), $now->copy()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'last_quarter' => [$lastQuarter->copy()->startOfQuarter(), $lastQuarter->copy()->endOfQuarter()],
            default => [null, null],
        };

        if ($start && $end) {
            $query->whereBetween($column, [$start, $end]);
        }
    }

    protected function applyDealValue(Builder $query, mixed $from, mixed $to, mixed $applied): void
    {
        if (! $this->dealIsActive(['deal_value_from' => $from, 'deal_value_to' => $to])) {
            return;
        }

        $from = $from === '' || $from === null ? null : (float) $from;
        $to = $to === '' || $to === null ? null : (float) $to;
        if ($from === null && $to === null) {
            return;
        }

        $query->where(function (Builder $q) use ($from, $to) {
            $q->whereHas('opportunities', function (Builder $opp) use ($from, $to) {
                if ($from !== null) {
                    $opp->where('estimated_value', '>=', $from);
                }
                if ($to !== null) {
                    $opp->where('estimated_value', '<=', $to);
                }
            })->orWhere(function (Builder $budget) use ($from, $to) {
                $budget->whereDoesntHave('opportunities');
                if ($from !== null) {
                    $budget->where('max_budget', '>=', $from);
                }
                if ($to !== null) {
                    $budget->where('max_budget', '<=', $to);
                }
            });
        });
    }

    protected function applyTaskTypes(Builder $query, array $types): void
    {
        $types = array_values(array_filter($types));
        if (! $types) {
            return;
        }

        $expanded = $types;
        foreach ($types as $type) {
            if ($type === 'text') {
                $expanded[] = 'sms';
            }
            if ($type === 'sms') {
                $expanded[] = 'text';
            }
        }

        $query->whereHas('tasks', function (Builder $q) use ($expanded) {
            $q->incomplete()->whereIn('type', array_unique($expanded));
        });
    }

    protected function applyTaskStatus(Builder $query, array $statuses): void
    {
        $statuses = array_values(array_filter($statuses));
        if (! $statuses) {
            return;
        }

        $now = now();
        $query->where(function (Builder $q) use ($statuses, $now) {
            if (in_array('due', $statuses, true)) {
                $q->orWhereHas('tasks', fn (Builder $t) => $t->incomplete()->where('due_at', '>=', $now));
            }
            if (in_array('overdue', $statuses, true)) {
                $q->orWhereHas('tasks', fn (Builder $t) => $t->incomplete()->whereNotNull('due_at')->where('due_at', '<', $now));
            }
            if (array_intersect($statuses, ['no_tasks', 'no_tasks_set'])) {
                $q->orWhereDoesntHave('tasks', fn (Builder $t) => $t->incomplete());
            }
        });
    }

    protected function applyCallsMade(Builder $query, array $filters): void
    {
        if (! $this->countIsActive($filters, 'calls')) {
            return;
        }

        $operator = $filters['calls_operator'] ?? 'more';
        $value = (int) ($filters['calls_value'] ?? 0);
        $sqlOp = match ($operator) {
            'less' => '<',
            'equal' => '=',
            default => '>',
        };

        $query->whereHas('activities', fn (Builder $q) => $q->where('type', 'call'), $sqlOp, $value);
    }

    protected function applySources(Builder $query, array $filters): void
    {
        $ids = LeadSource::expandAliasIds(array_values(array_filter(array_map('intval', Arr::wrap($filters['sources'] ?? [])))));
        if ($ids) {
            $query->whereIn('source_id', $ids);
        }
        if (filled($filters['source_medium'] ?? null)) {
            $query->where('medium', $filters['source_medium']);
        }
        if (filled($filters['source_utm_source'] ?? null)) {
            $query->where('utm_source', $filters['source_utm_source']);
        }
        if (filled($filters['source_utm_medium'] ?? null)) {
            $query->where('utm_medium', $filters['source_utm_medium']);
        }
    }

    protected function applyTimesAssigned(Builder $query, array $filters): void
    {
        if (! $this->countIsActive($filters, 'times_assigned')) {
            return;
        }

        $operator = $filters['times_assigned_operator'] ?? 'more';
        $value = (int) ($filters['times_assigned_value'] ?? 0);
        $columnOp = match ($operator) {
            'less' => '<',
            'equal' => '=',
            default => '>',
        };

        $query->where('times_assigned', $columnOp, $value);
    }

    protected function relativeIsActive(array $filters, string $prefix): bool
    {
        if (! empty($filters[$prefix.'_applied'])) {
            return true;
        }

        return isset($filters[$prefix.'_operator']) && ($filters[$prefix.'_value'] !== null && $filters[$prefix.'_value'] !== '');
    }

    protected function dateIsActive(array $filters, string $prefix): bool
    {
        return filled($filters[$prefix.'_date'] ?? null)
            || filled($filters[$prefix.'_from'] ?? null)
            || filled($filters[$prefix.'_to'] ?? null);
    }

    protected function dealIsActive(array $filters): bool
    {
        $from = $filters['deal_value_from'] ?? null;
        $to = $filters['deal_value_to'] ?? null;

        return ($from !== null && $from !== '') || ($to !== null && $to !== '');
    }

    protected function countIsActive(array $filters, string $prefix): bool
    {
        if (! empty($filters[$prefix.'_applied'])) {
            return isset($filters[$prefix.'_value']) && $filters[$prefix.'_value'] !== '';
        }

        return isset($filters[$prefix.'_operator']) && ($filters[$prefix.'_value'] !== null && $filters[$prefix.'_value'] !== '');
    }

    protected function sourceAdvancedActive(array $filters): bool
    {
        return filled($filters['source_medium'] ?? null)
            || filled($filters['source_utm_source'] ?? null)
            || filled($filters['source_utm_medium'] ?? null);
    }
}
