@extends('layouts.crm')
@section('title', $campaign->name)
@section('content')
<div class="crm-topbar">
    <div>
        <p class="kicker">Campaign</p>
        <h1 class="crm-title d-inline">{{ $campaign->name }}</h1>
        <p class="crm-subtitle">{{ $campaign->objective ?: 'Campaign detail' }} · {{ $campaign->source->name ?? 'No source' }}</p>
    </div>
    <div class="crm-actions">
        <a href="{{ route('crm.campaigns.index') }}" class="btn btn-outline-soft">← Back</a>
        <a href="{{ route('crm.leads.index', ['campaigns' => [$campaign->id]]) }}" class="btn btn-outline-soft">View Leads</a>
        @can('update', $campaign)
            <button type="button" class="btn btn-outline-soft"
                data-assign-open
                data-assign-url="{{ route('crm.campaigns.assign', $campaign) }}"
                data-assign-name="{{ $campaign->name }}"
                data-assign-users="{{ $campaign->users->pluck('id')->implode(',') }}">
                Assign Agents
            </button>
        @endcan
        <a href="{{ route('crm.campaigns.edit', $campaign) }}" class="btn btn-primary">Edit Campaign</a>
    </div>
</div>
<div class="crm-content">
    <div class="row g-3 mb-3">
        <div class="col">@include('components.kpi-card', ['label'=>'Leads','value'=>$campaign->total_leads,'icon'=>'bi-people','color'=>'blue','change'=>18])</div>
        <div class="col">@include('components.kpi-card', ['label'=>'Qualified','value'=>$campaign->qualified_leads,'icon'=>'bi-patch-check','color'=>'green','change'=>8])</div>
        <div class="col">@include('components.kpi-card', ['label'=>'Total Spend','value'=>auth()->user()->organization->formatMoney($campaign->total_spend),'icon'=>'bi-cash-coin','color'=>'yellow','change'=>5])</div>
        <div class="col">@include('components.kpi-card', ['label'=>'Cost / Lead','value'=>auth()->user()->organization->formatMoney($campaign->costPerLead()),'icon'=>'bi-tag','color'=>'purple','change'=>0])</div>
        <div class="col">@include('components.kpi-card', ['label'=>'ROI','value'=>$campaign->roi().'x','icon'=>'bi-graph-up-arrow','color'=>'pink','change'=>12])</div>
    </div>
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card-crm p-3 mb-3">
                <div class="card-head"><h5>Campaign Overview</h5><span class="badge-soft {{ $campaign->status==='active'?'badge-active':($campaign->status==='paused'?'badge-paused':'badge-done') }}">{{ ucfirst($campaign->status) }}</span></div>
                <div class="row g-3 small">
                    <div class="col-md-4"><div class="text-muted">Channel</div><strong class="d-inline-flex align-items-center gap-1">@include('components.source-icon', ['source'=>$campaign->source]) {{ $campaign->source->name ?? '—' }}</strong></div>
                    <div class="col-md-4"><div class="text-muted">Budget</div><strong>{{ auth()->user()->organization->formatMoney($campaign->budget) }}</strong></div>
                    <div class="col-md-4"><div class="text-muted">Routing</div><strong>{{ str_replace('_',' ', $campaign->routing_method ?: 'round robin') }}</strong></div>
                    <div class="col-md-4"><div class="text-muted">Sync</div><strong class="text-success"><i class="bi bi-check-circle-fill"></i> {{ ucfirst($campaign->sync_status ?: 'synced') }}</strong></div>
                    <div class="col-md-4"><div class="text-muted">Assigned Agents</div>
                        <div class="avatar-stack mt-1">
                            @foreach($campaign->users as $user)<div class="avatar" style="width:26px;height:26px;font-size:10px" title="{{ $user->name }}">{{ $user->initials() }}</div>@endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-crm table-wrap">
                <div class="p-3"><h5 class="mb-0">Recent Leads</h5></div>
                <table class="table-crm">
                    <thead><tr><th>Name</th><th>Phone</th><th>Score</th><th>Assigned</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($campaign->leads()->latest()->limit(12)->get() as $lead)
                        <tr>
                            <td><div class="d-flex align-items-center gap-2"><div class="avatar">{{ $lead->initials() }}</div><strong>{{ $lead->full_name }}</strong></div></td>
                            <td>{{ $lead->phone ?: '—' }}</td>
                            <td><span class="score-box {{ ($lead->lead_score??0)>=80?'hi':(($lead->lead_score??0)>=60?'mid':'lo') }}">{{ $lead->lead_score ?? 0 }}</span></td>
                            <td>{{ $lead->assignedUser->name ?? 'Unassigned' }}</td>
                            <td><span class="badge-soft badge-active">{{ $lead->stage->name ?? $lead->status }}</span></td>
                            <td><a href="{{ route('crm.leads.show', $lead) }}" class="btn btn-primary btn-sm">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">No leads on this campaign yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card-crm p-3 mb-3" style="background:var(--cp-bg)">
                <h6 class="fw-bold mb-3">AI Recommendations <span class="badge-soft badge-score-mid">Beta</span></h6>
                <div class="ai-rec green"><strong>Suggested Budget</strong><div>{{ auth()->user()->organization->formatMoney($recommendations['suggested_budget']) }}</div></div>
                <div class="ai-rec pink"><strong>Best Time to Post</strong><div>{{ $recommendations['best_posting_time'] }}</div></div>
                <div class="ai-rec purple"><strong>Target Audience</strong><div>{{ $recommendations['suggested_audience'] }}</div></div>
                <div class="ai-rec yellow"><strong>Expected CPL</strong><div>{{ auth()->user()->organization->formatMoney($recommendations['expected_cpl']) }}</div></div>
                <a href="{{ route('crm.ai.index') }}" class="btn btn-outline-soft w-100 btn-sm">Optimize with AI Copilot</a>
            </div>
            <div class="card-crm p-3">
                <h6 class="fw-bold mb-2">Assigned Agents</h6>
                @forelse($campaign->users as $user)
                    <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                        <div class="avatar">{{ $user->initials() }}</div>
                        <div><strong>{{ $user->name }}</strong><div class="small text-muted">{{ $user->roleName() }}</div></div>
                    </div>
                @empty
                    <div class="text-muted">No agents assigned.</div>
                @endforelse
                @can('update', $campaign)
                    <button type="button" class="btn btn-primary btn-sm w-100 mt-3"
                        data-assign-open
                        data-assign-url="{{ route('crm.campaigns.assign', $campaign) }}"
                        data-assign-name="{{ $campaign->name }}"
                        data-assign-users="{{ $campaign->users->pluck('id')->implode(',') }}">
                        Assign agents
                    </button>
                @endcan
            </div>
        </div>
    </div>
</div>
@include('crm.campaigns._assign-agents')
@endsection
