<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Jobs\ProcessCsvImport;
use App\Models\Campaign;
use App\Models\CsvImport;
use App\Models\Integration;
use App\Models\LeadSource;
use App\Models\User;
use App\Services\AI\CampaignRecommendationService;
use App\Services\CampaignService;
use App\Services\ExportService;
use App\Services\Mobile\AgentAccessService;
use App\Services\OrganizationProvisioner;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request, AgentAccessService $access)
    {
        $this->authorize('viewAny', Campaign::class);
        $org = $request->user()->organization;
        if (! $access->isScopedAgent($request->user())) {
            app(OrganizationProvisioner::class)->repairEmptyRolePermissions($org);
        }
        $businessId = trim((string) $request->get('business_id'));
        $query = $access->campaignQuery($request->user())->with(['source', 'users'])
            ->when($request->source_id, fn ($q, $id) => $q->where('source_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', '%'.$s.'%'))
            ->when($businessId !== '', fn ($q) => $q->where('meta_business_id', $businessId));

        $campaigns = $query->latest()->paginate(10)->withQueryString();
        $scoped = $access->campaignQuery($request->user())->when($businessId !== '', fn ($q) => $q->where('meta_business_id', $businessId));
        $kpis = [
            'active' => (clone $scoped)->where('status', 'active')->count(),
            'leads' => $access->leadQuery($request->user())
                ->where('created_at', '>=', now()->startOfWeek())
                ->when($businessId !== '', fn ($q) => $q->whereHas('campaign', fn ($c) => $c->where('meta_business_id', $businessId)))
                ->count(),
            'qualified_rate' => $this->qualifiedRate($org, $businessId !== '' ? $businessId : null),
            'spend' => (clone $scoped)->sum('total_spend'),
            'roi' => $this->roiFromQuery($scoped),
        ];
        $metaBusinesses = Campaign::forOrganization($org->id)
            ->whereNotNull('meta_business_id')
            ->where('meta_business_id', '!=', '')
            ->select('meta_business_id', 'meta_business_name')
            ->distinct()
            ->orderBy('meta_business_name')
            ->get();

        return view('crm.campaigns.index', [
            'campaigns' => $campaigns,
            'kpis' => $kpis,
            'sources' => LeadSource::forOrganization($org->id)->get(),
            'teams' => $org->teams,
            'users' => User::forOrganization($org->id)->where('status', 'active')->with('roles')->orderBy('name')->get(),
            'metaBusinesses' => $metaBusinesses,
            'isScopedAgent' => $access->isScopedAgent($request->user()),
        ]);
    }

    public function create(Request $request, CampaignRecommendationService $ai)
    {
        $this->authorize('create', Campaign::class);
        $org = $request->user()->organization;
        $campaign = new Campaign(['wizard_step' => 1, 'status' => 'draft']);

        return view('crm.campaigns.wizard', [
            'campaign' => $campaign,
            'step' => (int) $request->get('step', 1),
            'sources' => LeadSource::forOrganization($org->id)->get(),
            'users' => User::forOrganization($org->id)->where('status', 'active')->get(),
            'recommendations' => $ai->recommend($campaign),
            'connectedProviders' => $this->connectedProviders($org->id),
        ]);
    }

    public function store(StoreCampaignRequest $request, CampaignService $service)
    {
        $campaign = $service->create($request->user()->organization, $request->validated(), $request->user());

        return redirect()->route('crm.campaigns.edit', ['campaign' => $campaign, 'step' => min(8, ((int) $request->wizard_step) + 1)])
            ->with('success', 'Campaign saved.');
    }

    public function show(Campaign $campaign, CampaignRecommendationService $ai)
    {
        $this->authorize('view', $campaign);
        $campaign->load(['source', 'users', 'leads.assignedUser']);

        return view('crm.campaigns.show', [
            'campaign' => $campaign,
            'recommendations' => $ai->recommend($campaign),
            'users' => User::forOrganization($campaign->organization_id)->where('status', 'active')->with('roles')->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, Campaign $campaign, CampaignRecommendationService $ai)
    {
        $this->authorize('update', $campaign);

        return view('crm.campaigns.wizard', [
            'campaign' => $campaign,
            'step' => (int) $request->get('step', $campaign->wizard_step ?: 1),
            'sources' => LeadSource::forOrganization($campaign->organization_id)->get(),
            'users' => User::forOrganization($campaign->organization_id)->where('status', 'active')->get(),
            'recommendations' => $ai->recommend($campaign),
            'connectedProviders' => $this->connectedProviders($campaign->organization_id),
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign, CampaignService $service)
    {
        $this->authorize('update', $campaign);
        $data = $request->validated();
        if ($request->boolean('activate')) {
            $data['status'] = 'active';
        }
        $service->update($campaign, $data, $request->user());

        $step = (int) $request->input('wizard_step', $campaign->wizard_step ?: 1);
        if ($request->boolean('activate') || $step >= 8) {
            return redirect()->route('crm.campaigns.show', $campaign)
                ->with('success', $request->boolean('activate') ? 'Campaign activated.' : 'Campaign updated.');
        }

        return redirect()->route('crm.campaigns.edit', ['campaign' => $campaign, 'step' => min(8, $step + 1)])
            ->with('success', 'Campaign saved.');
    }

    public function assign(Request $request, Campaign $campaign, CampaignService $service)
    {
        $this->authorize('update', $campaign);
        $data = $request->validate([
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
        ]);
        $service->assignUsers($campaign, $data['user_ids'] ?? [], $request->user());

        return back()->with('success', 'Agents assigned. They will only see this campaign’s leads.');
    }

    public function destroy(Campaign $campaign)
    {
        $this->authorize('delete', $campaign);
        $campaign->delete();

        return redirect()->route('crm.campaigns.index')->with('success', 'Campaign deleted.');
    }

    public function export(Request $request, ExportService $export)
    {
        abort_unless($request->user()->hasPermission('campaigns.view'), 403);
        $rows = Campaign::forOrganization($request->user()->organization_id)->get();

        return $export->csv('campaigns.csv', ['Name', 'Status', 'Leads', 'Spend'], $rows->map(fn ($c) => [
            $c->name, $c->status, $c->total_leads, $c->total_spend,
        ]));
    }

    public function importForm(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        return view('crm.campaigns.import', compact('campaign'));
    }

    public function import(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $path = $request->file('file')->store('imports');
        $import = CsvImport::create([
            'organization_id' => $campaign->organization_id,
            'user_id' => $request->user()->id,
            'campaign_id' => $campaign->id,
            'filename' => $request->file('file')->getClientOriginalName(),
            'stored_path' => $path,
            'column_map' => [
                'first_name' => 0, 'last_name' => 1, 'email' => 2, 'phone' => 3, 'city' => 4, 'interested_in' => 5,
            ],
            'status' => 'uploaded',
        ]);
        ProcessCsvImport::dispatch($import);

        return back()->with('success', 'CSV import queued.');
    }

    protected function connectedProviders(int $organizationId): array
    {
        return Integration::forOrganization($organizationId)
            ->where('status', 'connected')
            ->pluck('provider')
            ->all();
    }

    protected function qualifiedRate($org, ?string $businessId = null): float
    {
        $leads = $org->leads()->when($businessId, fn ($q) => $q->whereHas('campaign', fn ($c) => $c->where('meta_business_id', $businessId)));
        $total = max(1, (clone $leads)->count());

        return round(((clone $leads)->where('lead_score', '>=', 60)->count() / $total) * 100, 1);
    }

    protected function roiFromQuery($query): float
    {
        $spend = (float) (clone $query)->sum('total_spend');
        $rev = (float) (clone $query)->sum('revenue');

        return $spend > 0 ? round($rev / $spend, 2) : 0;
    }

    protected function roi($org): float
    {
        return $this->roiFromQuery($org->campaigns());
    }
}
