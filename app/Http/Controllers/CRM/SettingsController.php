<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Models\CustomField;
use App\Models\DuplicateCandidate;
use App\Models\Integration;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Permission;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('settings.view'), 403);
        $org = $request->user()->organization;
        $tab = $request->get('tab', 'organization');
        $pipelines = $org->pipelines()->with('stages')->get();
        $activePipeline = $pipelines->firstWhere('id', (int) $request->get('pipeline_id'))
            ?? $pipelines->firstWhere('is_default', true)
            ?? $pipelines->first();

        $integrations = Integration::forOrganization($org->id)->get();
        $automations = AutomationRule::forOrganization($org->id)->latest()->get();
        $fields = CustomField::forOrganization($org->id)->orderBy('position')->get();

        return view('crm.settings.index', [
            'org' => $org,
            'tab' => $tab,
            'pipelines' => $pipelines,
            'activePipeline' => $activePipeline,
            'pipelineStages' => $this->pipelineStages($org->id, $activePipeline),
            'fields' => $fields,
            'topFields' => $this->topFields($org, $fields),
            'tags' => Tag::forOrganization($org->id)->get(),
            'roles' => Role::forOrganization($org->id)->with('permissions')->get(),
            'overviewRoles' => Role::forOrganization($org->id)->with('permissions')
                ->whereIn('slug', ['administrator', 'manager', 'agent', 'viewer'])->get()
                ->sortBy(fn ($role) => array_search($role->slug, ['administrator', 'manager', 'agent', 'viewer'], true))
                ->values(),
            'permissions' => Permission::all(),
            'permissionModules' => $this->permissionModules(),
            'users' => $org->users,
            'teams' => $org->teams,
            'integrations' => $integrations,
            'integrationCatalog' => $this->integrationCatalog($integrations),
            'automations' => $automations,
            'kpis' => $this->kpis($org, $integrations, $automations),
            'efficiency' => $this->efficiency($org, $automations),
            'duplicateStats' => $this->duplicateStats($org),
            'canManage' => $request->user()->hasPermission('settings.manage'),
            'canManageAutomations' => $request->user()->hasPermission('automations.manage'),
            'routingMethods' => $this->routingMethods(),
            'routingMethod' => data_get($org->settings, 'default_routing_method', 'round_robin'),
        ]);
    }

    public function updateOrganization(Request $request)
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);
        $org = $request->user()->organization;
        $codes = array_keys(config('crm.currencies', ['USD' => 'USD']));
        $current = strtoupper((string) $org->currency);
        if ($current !== '' && ! in_array($current, $codes, true)) {
            $codes[] = $current;
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'industry' => ['required', 'string'],
            'currency' => ['required', 'string', 'max:8', Rule::in($codes)],
            'country' => ['nullable', 'string'],
            'timezone' => ['required', 'string'],
            'language' => ['nullable', 'string'],
            'phone_country' => ['nullable', 'string'],
            'date_format' => ['nullable', 'string'],
            'sla_minutes' => ['nullable', 'integer', 'min:1'],
        ]);
        $data['currency'] = strtoupper($data['currency']);
        $org->update($data);
        $org->propagateCurrency($data['currency']);

        return back()->with('success', 'Organization updated. Portal currency is now '.$data['currency'].'.');
    }

    public function storePipeline(Request $request)
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);
        $pipeline = Pipeline::create([
            'organization_id' => $request->user()->organization_id,
            'name' => $request->validate(['name' => 'required|string|max:190'])['name'],
            'entity_type' => $request->get('entity_type', 'lead'),
            'status' => 'active',
        ]);
        foreach (config('crm.default_pipeline_stages') as $i => $stage) {
            PipelineStage::create($stage + ['pipeline_id' => $pipeline->id, 'position' => $i + 1, 'active' => true]);
        }

        return back()->with('success', 'Pipeline created.');
    }

    public function storeStage(Request $request, Pipeline $pipeline)
    {
        abort_unless($request->user()->organization_id === $pipeline->organization_id, 403);
        PipelineStage::create($request->validate([
            'name' => 'required|string',
            'color' => 'nullable|string',
            'type' => 'nullable|string',
            'probability' => 'nullable|integer',
        ]) + [
            'pipeline_id' => $pipeline->id,
            'slug' => Str::slug($request->name),
            'position' => $pipeline->stages()->max('position') + 1,
            'active' => true,
        ]);

        return back()->with('success', 'Stage added.');
    }

    public function storeField(Request $request)
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);
        CustomField::create($request->validate([
            'entity_type' => 'required|string',
            'name' => 'required|string',
            'type' => 'required|string',
            'section' => 'nullable|string',
        ]) + [
            'organization_id' => $request->user()->organization_id,
            'slug' => Str::slug($request->name),
            'active' => true,
        ]);

        return back()->with('success', 'Custom field added.');
    }

    public function storeTag(Request $request)
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);
        Tag::create($request->validate([
            'name' => 'required|string',
            'color' => 'nullable|string',
        ]) + ['organization_id' => $request->user()->organization_id, 'active' => true]);

        return back()->with('success', 'Tag added.');
    }

    public function updateRole(Request $request, Role $role)
    {
        abort_unless($request->user()->organization_id === $role->organization_id && $request->user()->hasPermission('settings.manage'), 403);
        $role->permissions()->sync($request->permissions ?? []);

        return back()->with('success', 'Permissions updated.');
    }

    public function updateDuplicates(Request $request)
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);
        $org = $request->user()->organization;
        $settings = $org->settings ?? [];
        $settings['duplicate_detection'] = $request->boolean('duplicate_detection');
        $settings['merge_suggestions'] = $request->boolean('merge_suggestions');
        $settings['auto_merge'] = $request->boolean('auto_merge');
        $org->update(['settings' => $settings]);

        return back()->with('success', 'Duplicate settings saved.');
    }

    public function updateRouting(Request $request)
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);
        $method = $request->validate([
            'default_routing_method' => ['required', 'in:round_robin,least_assigned,performance,location,weighted'],
        ])['default_routing_method'];
        $org = $request->user()->organization;
        $settings = $org->settings ?? [];
        $settings['default_routing_method'] = $method;
        $org->update(['settings' => $settings]);

        return back()->with('success', 'Lead routing updated.');
    }

    public function toggleAutomation(Request $request, AutomationRule $automation)
    {
        abort_unless($request->user()->organization_id === $automation->organization_id, 403);
        abort_unless($request->user()->hasPermission('automations.manage'), 403);
        $automation->update([
            'status' => $automation->status === 'active' ? 'paused' : 'active',
        ]);

        return back()->with('success', 'Automation updated.');
    }

    protected function kpis($org, $integrations, $automations): array
    {
        $since = now()->subDays(30);

        return [
            [
                'label' => 'Total Users',
                'value' => $org->users()->count(),
                'icon' => 'bi-people',
                'change' => $this->growthChange($org->users()->count(), $org->users()->where('created_at', '<', $since)->count()),
            ],
            [
                'label' => 'Total Teams',
                'value' => $org->teams()->count(),
                'icon' => 'bi-diagram-3',
                'change' => $this->growthChange($org->teams()->count(), $org->teams()->where('created_at', '<', $since)->count()),
            ],
            [
                'label' => 'Active Integrations',
                'value' => $integrations->where('status', 'connected')->count(),
                'icon' => 'bi-puzzle',
                'change' => $this->growthChange(
                    $integrations->where('status', 'connected')->count(),
                    $integrations->where('status', 'connected')->filter(fn ($row) => $row->created_at && $row->created_at->lt($since))->count()
                ),
            ],
            [
                'label' => 'Active Automations',
                'value' => $automations->where('status', 'active')->count(),
                'icon' => 'bi-lightning-charge',
                'change' => $this->growthChange(
                    $automations->where('status', 'active')->count(),
                    $automations->where('status', 'active')->filter(fn ($row) => $row->created_at && $row->created_at->lt($since))->count()
                ),
            ],
        ];
    }

    protected function efficiency($org, $automations): array
    {
        $runs = (int) $automations->sum('runs_count');
        $active = $automations->where('status', 'active')->count();
        $total = max(1, $automations->count());
        $pipelineValue = (float) Opportunity::forOrganization($org->id)->sum('estimated_value');
        $percent = $automations->isEmpty()
            ? 0
            : min(99, (int) round(62 + (($active / $total) * 22) + min(16, $runs / 80)));

        return [
            'percent' => $percent,
            'tasks' => $runs,
            'hours' => (int) round($runs * 0.15),
            'pipeline' => $this->compactNumber($pipelineValue),
            'change' => $active ? min(99, $active * 8) : 0,
        ];
    }

    protected function duplicateStats($org): array
    {
        $leads = max(1, Lead::forOrganization($org->id)->count());
        $pending = DuplicateCandidate::forOrganization($org->id)->where('status', 'pending')->count();
        $reviewed = DuplicateCandidate::forOrganization($org->id)
            ->whereNotNull('reviewed_at')
            ->where('reviewed_at', '>=', now()->startOfMonth())
            ->count();
        $merged = DuplicateCandidate::forOrganization($org->id)
            ->where('status', 'merged')
            ->where('reviewed_at', '>=', now()->startOfMonth())
            ->count();
        $rate = round(($pending / $leads) * 100, 1);

        return [
            'rate' => $rate,
            'rate_label' => $rate < 5 ? 'Low' : ($rate < 15 ? 'Medium' : 'High'),
            'reviewed' => $reviewed,
            'merged' => $merged,
            'pending' => $pending,
        ];
    }

    protected function pipelineStages(int $orgId, $pipeline): array
    {
        if (! $pipeline) {
            return [];
        }

        $counts = Lead::forOrganization($orgId)
            ->selectRaw('pipeline_stage_id, count(*) as total')
            ->groupBy('pipeline_stage_id')
            ->pluck('total', 'pipeline_stage_id');

        $stages = $pipeline->stages->take(6)->values();
        if ($stages->isEmpty()) {
            return [];
        }
        $fromHere = [];
        $running = 0;
        for ($i = $stages->count() - 1; $i >= 0; $i--) {
            $running += (int) ($counts[$stages[$i]->id] ?? 0);
            $fromHere[$stages[$i]->id] = $running;
        }
        $first = max(1, $fromHere[$stages->first()->id ?? 0] ?? $counts->sum() ?: 1);
        $pastels = ['#dbeafe', '#e0f2fe', '#ede9fe', '#ffedd5', '#fee2e2', '#dcfce7'];
        $inks = ['#1d4ed8', '#0369a1', '#6d28d9', '#c2410c', '#b91c1c', '#15803d'];

        return $stages->values()->map(function ($stage, $i) use ($fromHere, $first, $pastels, $inks) {
            return [
                'name' => $stage->name,
                'percent' => (int) round((($fromHere[$stage->id] ?? 0) / $first) * 100),
                'bg' => $pastels[$i % count($pastels)],
                'color' => $stage->color ?: $inks[$i % count($inks)],
            ];
        })->all();
    }

    protected function topFields($org, $fields)
    {
        if ($fields->isNotEmpty()) {
            return $fields->take(4);
        }

        return collect([
            (object) ['name' => 'Deal Value ['.$org->currency.']', 'type' => 'number'],
            (object) ['name' => 'Property Type', 'type' => 'dropdown'],
            (object) ['name' => 'Location', 'type' => 'dropdown'],
            (object) ['name' => 'Preferred Budget', 'type' => 'number'],
        ]);
    }

    protected function integrationCatalog($integrations)
    {
        $catalog = collect([
            ['provider' => 'meta', 'name' => 'Meta Ads', 'category' => 'Leads'],
            ['provider' => 'tiktok', 'name' => 'TikTok Ads', 'category' => 'Leads'],
            ['provider' => 'bayut', 'name' => 'Bayut', 'category' => 'Leads'],
            ['provider' => 'google', 'name' => 'Google Ads', 'category' => 'Leads'],
            ['provider' => 'whatsapp', 'name' => 'WhatsApp', 'category' => 'Events'],
            ['provider' => 'website', 'name' => 'Website Forms', 'category' => 'Leads'],
            ['provider' => 'email', 'name' => 'Email', 'category' => 'Events'],
        ]);

        $known = $catalog->pluck('provider');
        foreach ($integrations as $item) {
            if (! $known->contains($item->provider)) {
                $catalog->push([
                    'provider' => $item->provider,
                    'name' => $item->name,
                    'category' => 'Leads',
                ]);
            }
        }

        return $catalog->take(7)->map(function ($item) use ($integrations) {
            $row = $integrations->firstWhere('provider', $item['provider']);
            $item['status'] = $row->status ?? 'disconnected';
            $item['connected'] = ($row->status ?? '') === 'connected';

            return $item;
        });
    }

    protected function permissionModules(): array
    {
        return [
            'Dashboard' => 'dashboard.view',
            'Leads' => 'leads.view',
            'Campaigns' => 'campaigns.view',
            'Automations' => 'automations.view',
            'Integrations' => 'integrations.view',
            'Reports' => 'reports.view',
            'Settings' => 'settings.view',
        ];
    }

    protected function routingMethods(): array
    {
        return [
            'round_robin' => ['Round Robin', 'Rotate new leads evenly across eligible agents.', 'bi-arrow-repeat'],
            'least_assigned' => ['Least Assigned', 'Send the next lead to whoever currently has the fewest open leads.', 'bi-bar-chart'],
            'performance' => ['Performance Based', 'Prefer agents with stronger conversion on similar leads.', 'bi-trophy'],
            'location' => ['Location Based', 'Match leads to teams or agents covering the same city or area.', 'bi-geo-alt'],
            'weighted' => ['Weighted', 'Use campaign weights so some agents receive a larger share.', 'bi-sliders'],
        ];
    }

    protected function growthChange(int $current, int $previous): int
    {
        if ($previous <= 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    protected function compactNumber(float $n): string
    {
        $abs = abs($n);
        if ($abs >= 1000000) {
            return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.').'M';
        }
        if ($abs >= 1000) {
            return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.').'K';
        }

        return number_format($n);
    }
}
