<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadFilterRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\DuplicateCandidate;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use App\Services\AI\DuplicateAnalysisService;
use App\Services\AI\LeadScoringService;
use App\Services\AI\LeadSummaryService;
use App\Services\AI\MessageSuggestionService;
use App\Services\AI\RecommendedActionService;
use App\Services\AI\SentimentService;
use App\Services\ActivityService;
use App\Services\DuplicateService;
use App\Services\ExportService;
use App\Services\LeadFilterService;
use App\Services\LeadService;
use App\Services\Mobile\AgentAccessService;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(LeadFilterRequest $request, LeadFilterService $filterService, AgentAccessService $access)
    {
        $this->authorize('viewAny', Lead::class);
        $org = $request->user()->organization;
        $tab = $request->get('tab', 'all');
        $filters = $request->validated();
        $isScopedAgent = $access->isScopedAgent($request->user());

        $base = $access->leadQuery($request->user());
        $query = $filterService->apply((clone $base)->with(['source', 'campaign', 'assignedUser', 'stage', 'tags']), $filters, $request->user());

        $counts = [
            'all' => (clone $base)->count(),
            'mine' => (clone $base)->where('assigned_user_id', $request->user()->id)->count(),
            'hot' => (clone $base)->where('lead_score', '>=', 80)->count(),
            'high' => (clone $base)->where('lead_score', '>=', 70)->count(),
            'duplicates' => $isScopedAgent ? 0 : DuplicateCandidate::forOrganization($org->id)->where('status', 'pending')->count(),
            'unassigned' => (clone $base)->whereNull('assigned_user_id')->count(),
            'overdue' => (clone $base)->where('next_followup_at', '<', now())->count(),
            'cold' => (clone $base)->where('lead_score', '<', 40)->count(),
        ];

        match ($tab) {
            'mine' => $query->where('assigned_user_id', $request->user()->id),
            'hot' => $query->where('lead_score', '>=', 80),
            'high' => $query->where('lead_score', '>=', 70),
            'duplicates' => $query->whereHas('duplicates', fn ($q) => $q->where('status', 'pending')),
            'unassigned' => $query->whereNull('assigned_user_id'),
            'overdue' => $query->where('next_followup_at', '<', now()),
            'cold' => $query->where('lead_score', '<', 40),
            default => null,
        };

        $sort = in_array($request->sort, ['created_at', 'lead_score', 'last_activity_at', 'first_name', 'updated_at'], true) ? $request->sort : 'created_at';
        $leads = $query->orderBy($sort, $request->get('dir', 'desc'))->paginate($request->get('per_page', 10))->withQueryString();
        $filterOptions = $filterService->options($org);
        if ($isScopedAgent) {
            $allowedCampaignIds = $access->campaignQuery($request->user())->pluck('id');
            $filterOptions['campaigns'] = collect($filterOptions['campaigns'])->whereIn('id', $allowedCampaignIds)->values();
        }
        $filterState = $filterService->state($filters);

        return view('crm.leads.index', [
            'leads' => $leads,
            'counts' => $counts,
            'tab' => $tab,
            'sources' => $filterOptions['sources'],
            'campaigns' => $filterOptions['campaigns'],
            'stages' => PipelineStage::whereIn('pipeline_id', $org->pipelines()->pluck('id'))->orderBy('position')->get(),
            'users' => $filterOptions['agents'],
            'teams' => Team::forOrganization($org->id)->get(),
            'tags' => $filterOptions['tags'],
            'filterOptions' => $filterOptions,
            'filterState' => $filterState,
            'isScopedAgent' => $isScopedAgent,
        ]);
    }

    public function create(Request $request, LeadScoringService $scoring, DuplicateAnalysisService $dupes)
    {
        $this->authorize('create', Lead::class);
        $org = $request->user()->organization;

        return view('crm.leads.form', $this->formData($org) + ['lead' => new Lead]);
    }

    public function store(StoreLeadRequest $request, LeadService $service, LeadScoringService $scoring, SentimentService $sentiment)
    {
        $lead = $service->create($request->user()->organization, $request->validated(), $request->user());
        $scoring->score($lead);
        $sentiment->analyze($lead);
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $service->storeAttachment($lead, $file, $request->user());
            }
        }

        return redirect()->route('crm.leads.show', $lead)->with('success', 'Lead created.');
    }

    public function activityShow(Lead $lead)
    {
        $this->authorize('view', $lead);
        $lead->load(['assignedUser', 'source', 'activities.user', 'tasks.assignedUser']);

        $lastActivity = $lead->activities->first();
        $nextTask = $lead->tasks
            ->where('status', '!=', 'completed')
            ->sortBy(fn ($task) => $task->due_at?->timestamp ?? PHP_INT_MAX)
            ->first();

        return view('crm.leads.activity', [
            'lead' => $lead,
            'lastActivity' => $lastActivity,
            'nextTask' => $nextTask,
        ]);
    }

    public function show(Request $request, Lead $lead, LeadSummaryService $summary, RecommendedActionService $actions, MessageSuggestionService $messages, DuplicateAnalysisService $dupes)
    {
        $this->authorize('view', $lead);
        $lead->load(['source', 'campaign', 'assignedUser', 'stage', 'tags', 'activities.user', 'notesList.user', 'attachments', 'tasks.assignedUser', 'opportunities', 'interests']);

        return view('crm.leads.show', [
            'lead' => $lead,
            'summary' => $summary->summarize($lead),
            'action' => $actions->recommend($lead),
            'whatsappDraft' => $messages->draft($lead, 'whatsapp'),
            'emailDraft' => $messages->draft($lead, 'email'),
            'duplicateRisk' => $dupes->risk($lead),
        ]);
    }

    public function edit(Lead $lead)
    {
        $this->authorize('update', $lead);

        return view('crm.leads.form', $this->formData($lead->organization) + ['lead' => $lead]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead, LeadService $service)
    {
        $this->authorize('update', $lead);
        $service->update($lead, $request->validated(), $request->user());
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $service->storeAttachment($lead, $file, $request->user());
            }
        }

        return redirect()->route('crm.leads.show', $lead)->with('success', 'Lead updated.');
    }

    public function destroy(Lead $lead)
    {
        $this->authorize('delete', $lead);
        $lead->delete();

        return redirect()->route('crm.leads.index')->with('success', 'Lead deleted.');
    }

    public function bulk(Request $request, LeadService $service)
    {
        $this->authorize('viewAny', Lead::class);
        $ids = array_filter(explode(',', (string) $request->ids));
        $org = $request->user()->organization;
        $count = match ($request->action) {
            'assign' => $service->bulkAssign($ids, (int) $request->assigned_user_id, $org, $request->user()),
            'status' => $service->bulkStatus($ids, (string) $request->status, $org, $request->user()),
            'stage' => $service->bulkStage($ids, (int) $request->pipeline_stage_id, $org, $request->user()),
            'tags' => $service->bulkTags($ids, (array) $request->tags, $org),
            default => 0,
        };

        return back()->with('success', "Updated {$count} leads.");
    }

    public function export(Request $request, ExportService $export)
    {
        abort_unless($request->user()->hasPermission('leads.export'), 403);
        $leads = app(AgentAccessService::class)->leadQuery($request->user())->with(['source', 'assignedUser'])->limit(5000)->get();

        return $export->csv('leads.csv', ['Name', 'Email', 'Phone', 'Source', 'Status', 'Score'], $leads->map(fn ($l) => [
            $l->full_name, $l->email, $l->phone, $l->source?->name, $l->status, $l->lead_score,
        ]));
    }

    public function note(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);
        $request->validate(['note' => ['required', 'string']]);
        LeadNote::create([
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'note' => $request->note,
        ]);
        app(ActivityService::class)->log($lead, 'note', $request->note, $request->user());

        return back()->with('success', 'Note added.');
    }

    public function activity(Request $request, Lead $lead, ActivityService $activities)
    {
        $this->authorize('update', $lead);
        $data = $request->validate([
            'type' => ['required', 'string'],
            'description' => ['nullable', 'string'],
        ]);
        $activities->log($lead, $data['type'], $data['description'] ?? null, $request->user());

        return redirect()->route('crm.leads.activity', $lead)->with('success', 'Activity logged.');
    }

    public function merge(Request $request, Lead $lead, DuplicateService $duplicates)
    {
        $this->authorize('update', $lead);
        $other = Lead::forOrganization($lead->organization_id)->findOrFail($request->duplicate_id);
        $duplicates->merge($lead, $other, $request->user());

        return redirect()->route('crm.leads.show', $lead)->with('success', 'Leads merged.');
    }

    public function duplicates(Request $request)
    {
        $this->authorize('viewAny', Lead::class);
        $items = DuplicateCandidate::forOrganization($request->user()->organization_id)
            ->with(['lead', 'duplicate'])->where('status', 'pending')->paginate(20);

        return view('crm.leads.duplicates', compact('items'));
    }

    protected function formData($org): array
    {
        return [
            'sources' => LeadSource::forOrganization($org->id)->get(),
            'campaigns' => $org->campaigns()->get(),
            'stages' => PipelineStage::whereIn('pipeline_id', $org->pipelines()->pluck('id'))->orderBy('position')->get(),
            'users' => User::forOrganization($org->id)->where('status', 'active')->get(),
            'teams' => Team::forOrganization($org->id)->get(),
            'tags' => Tag::forOrganization($org->id)->get(),
        ];
    }
}
