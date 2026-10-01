<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Requests\LeadFilterRequest;
use App\Http\Requests\Mobile\StoreActivityRequest;
use App\Http\Requests\Mobile\StoreFollowUpRequest;
use App\Http\Requests\Mobile\StoreLeadRequest;
use App\Http\Requests\Mobile\StoreNoteRequest;
use App\Http\Resources\Mobile\ActivityResource;
use App\Http\Resources\Mobile\LeadDetailResource;
use App\Http\Resources\Mobile\LeadResource;
use App\Http\Resources\Mobile\TaskResource;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Services\LeadFilterService;
use App\Services\LeadService;
use App\Services\Mobile\ActivityWorkflowService;
use App\Services\Mobile\AgentAccessService;
use Illuminate\Http\Request;

class LeadController extends MobileController
{
    public function __construct(
        protected AgentAccessService $access,
        protected LeadFilterService $filters,
        protected ActivityWorkflowService $workflow,
        protected LeadService $leads
    ) {
    }

    public function index(LeadFilterRequest $request)
    {
        $user = $request->user();
        $query = $this->access->leadQuery($user);
        $filters = $request->validated();
        $query = $this->filters->apply($query, $filters, $user);
        $this->applyTab($query, $request->get('tab', 'all'));

        if ($request->boolean('my_leads_only') || $request->boolean('overdue_only')) {
            if ($request->boolean('my_leads_only')) {
                $query->where('assigned_user_id', $user->id);
            }
            if ($request->boolean('overdue_only')) {
                $query->where(function ($q) {
                    $q->where('next_followup_at', '<', now())
                        ->orWhereHas('tasks', fn ($t) => $t->incomplete()->whereNotNull('due_at')->where('due_at', '<', now()));
                });
            }
        }

        $leads = $query
            ->with(['source', 'campaign', 'assignedUser', 'stage'])
            ->orderByDesc('last_activity_at')
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->get('per_page', 20), 50));

        return LeadResource::collection($leads)->additional(['success' => true]);
    }

    public function store(StoreLeadRequest $request)
    {
        $user = $request->user();
        $payload = $request->safe()->only([
            'first_name', 'last_name', 'phone', 'email', 'whatsapp', 'company', 'campaign_id', 'notes',
        ]);

        if (! empty($payload['campaign_id'])) {
            $campaign = Campaign::query()
                ->forOrganization($user->organization_id)
                ->whereKey($payload['campaign_id'])
                ->first();
            abort_unless($campaign, 422, 'Campaign not found.');
        }

        $sourceId = LeadSource::query()
            ->forOrganization($user->organization_id)
            ->where('slug', 'manual')
            ->value('id');

        $lead = $this->leads->create($user->organization, array_filter([
            ...$payload,
            'source_id' => $sourceId,
            'status' => 'not_contacted',
            'assigned_user_id' => $user->id,
            'whatsapp' => $payload['whatsapp'] ?? $payload['phone'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''), $user);

        return $this->ok(new LeadResource($lead->load(['source', 'campaign', 'assignedUser', 'stage'])), 'Lead created.', 201);
    }

    public function show(Request $request, Lead $lead)
    {
        $this->access->authorizeLead($request->user(), $lead);
        $lead->load([
            'source', 'campaign', 'assignedUser', 'stage',
            'activities.user', 'notesList.user', 'tasks.assignedUser', 'tasks.creator', 'tasks.lead',
        ]);

        return $this->ok(new LeadDetailResource($lead));
    }

    public function activities(Request $request, Lead $lead)
    {
        $this->access->authorizeLead($request->user(), $lead);
        $items = $lead->activities()->with('user')->latest('activity_at')->paginate(30);

        return ActivityResource::collection($items)->additional(['success' => true]);
    }

    public function storeActivity(StoreActivityRequest $request, Lead $lead)
    {
        $this->access->authorizeLead($request->user(), $lead);
        $activity = $this->workflow->record($lead, $request->user(), $request->validated());

        return $this->ok(new ActivityResource($activity), 'Activity saved.', 201);
    }

    public function storeNote(StoreNoteRequest $request, Lead $lead)
    {
        $this->access->authorizeLead($request->user(), $lead);
        $activity = $this->workflow->addNote($lead, $request->user(), $request->note);

        return $this->ok(new ActivityResource($activity), 'Note added.', 201);
    }

    public function storeFollowUp(StoreFollowUpRequest $request, Lead $lead)
    {
        $this->access->authorizeLead($request->user(), $lead);
        $result = $this->workflow->scheduleFollowUp($lead, $request->user(), $request->validated());

        return $this->ok([
            'task' => new TaskResource($result['task']->load(['lead', 'assignedUser', 'creator'])),
            'activity' => $result['activity'] ? new ActivityResource($result['activity']->load('user')) : null,
        ], 'Follow-up scheduled.', 201);
    }

    public function filters(Request $request)
    {
        $org = $request->user()->organization;
        $options = $this->filters->options($org);

        return $this->ok([
            'statuses' => $options['statuses'],
            'sources' => $options['sources']->map(fn (LeadSource $source) => [
                'id' => $source->id,
                'name' => $source->name,
                'slug' => $source->slug,
            ])->values(),
            'campaigns' => $options['campaigns']->map(fn (Campaign $campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
            ])->values(),
            'tabs' => [
                ['value' => 'all', 'label' => 'All'],
                ['value' => 'new', 'label' => 'New'],
                ['value' => 'follow_up', 'label' => 'Follow-up'],
                ['value' => 'qualified', 'label' => 'Qualified'],
                ['value' => 'working', 'label' => 'Working'],
                ['value' => 'lost', 'label' => 'Lost'],
            ],
        ]);
    }

    protected function applyTab($query, ?string $tab): void
    {
        match ($tab) {
            'new' => $query->where(function ($q) {
                $q->whereIn('status', config('mobile.new_lead_statuses', []))
                    ->orWhereHas('stage', fn ($s) => $s->whereIn('slug', ['new-lead', 'new']));
            }),
            'follow_up' => $query->where(function ($q) {
                $q->whereNotNull('next_followup_at')
                    ->orWhereHas('tasks', fn ($t) => $t->incomplete()->whereIn('type', ['follow_up', 'call']));
            }),
            'qualified' => $query->whereIn('status', config('mobile.qualified_statuses', [])),
            'working' => $query->whereIn('status', config('mobile.working_statuses', [])),
            'lost' => $query->whereIn('status', config('mobile.lost_statuses', [])),
            default => null,
        };
    }
}
