<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Task;
use App\Services\AI\LeadSummaryService;
use App\Services\AI\MessageSuggestionService;
use Illuminate\Http\Request;

class AiCopilotController extends Controller
{
    public function index(Request $request, MessageSuggestionService $messages)
    {
        abort_unless($request->user()->hasPermission('ai.view'), 403);
        $orgId = $request->user()->organization_id;
        $priority = Lead::forOrganization($orgId)->orderByDesc('lead_score')->limit(8)->get();
        $followups = Task::forOrganization($orgId)->where('status', '!=', 'completed')->orderBy('due_at')->limit(8)->with('lead.source')->get();
        $hotspots = Lead::forOrganization($orgId)->selectRaw('city, count(*) as total')->whereNotNull('city')->groupBy('city')->orderByDesc('total')->limit(6)->get();
        $high = Lead::forOrganization($orgId)->where('lead_score', '>=', 70)->count();
        $medium = Lead::forOrganization($orgId)->whereBetween('lead_score', [40, 69])->count();
        $low = Lead::forOrganization($orgId)->where('lead_score', '<', 40)->count();
        $sources = \App\Models\LeadSource::forOrganization($orgId)->get();
        $draftLead = $priority->first();
        $draft = $draftLead ? $messages->draft($draftLead, 'whatsapp') : 'Add leads to generate a message draft.';

        return view('crm.ai.index', compact('priority', 'followups', 'hotspots', 'high', 'medium', 'low', 'sources', 'draft', 'draftLead'));
    }

    public function ask(Request $request, LeadSummaryService $summary, MessageSuggestionService $messages)
    {
        abort_unless($request->user()->hasPermission('ai.view'), 403);
        $q = strtolower((string) $request->question);
        $orgId = $request->user()->organization_id;
        $leads = Lead::forOrganization($orgId)->orderByDesc('lead_score')->limit(5)->get();

        if (str_contains($q, 'whatsapp') || str_contains($q, 'draft')) {
            $lead = $leads->first();
            $answer = $lead ? $messages->draft($lead) : 'No leads available to draft a message.';
        } elseif (str_contains($q, 'summarize') && $leads->first()) {
            $answer = $summary->summarize($leads->first());
        } elseif (str_contains($q, 'campaign')) {
            $best = $request->user()->organization->campaigns()->orderByDesc('qualified_leads')->first();
            $answer = $best ? 'Best performing campaign: '.$best->name : 'No campaign data yet.';
        } else {
            $answer = $leads->isEmpty()
                ? 'No lead data yet. Add leads to get AI recommendations.'
                : 'Focus today on: '.$leads->take(3)->map(fn ($l) => $l->full_name.' (score '.$l->lead_score.')')->implode(', ');
        }

        return back()->with('ai_answer', $answer)->with('ai_question', $request->question);
    }
}
