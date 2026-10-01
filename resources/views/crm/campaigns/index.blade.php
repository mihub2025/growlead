@extends('layouts.crm')
@section('title', 'Campaigns')
@section('content')
<div class="crm-topbar">
    <div>
        <p class="kicker">{{ now()->format('l · j F Y') }}</p>
        <h1 class="crm-title">{{ !empty($isScopedAgent) ? 'My Campaigns' : 'Campaigns' }}</h1>
        <p class="crm-subtitle">{{ !empty($isScopedAgent) ? 'Only campaigns assigned to you. Open one to work its leads.' : 'Assign agents here. They will only see leads from their campaigns.' }}</p>
    </div>
    <div class="crm-actions">
        @can('campaigns.create')
            <a href="{{ route('crm.campaigns.create') }}" class="btn btn-primary">+ Create Campaign</a>
        @endcan
    </div>
</div>
<div class="crm-content">
    <form class="filters-bar mb-3 d-flex flex-wrap gap-2 align-items-center" method="GET">
        <div class="search-box" style="min-width:220px">
            <i class="bi bi-search"></i>
            <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search campaigns">
        </div>
        <select name="status" class="form-select" style="width:auto" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach(['draft'=>'Draft','active'=>'Active','paused'=>'Paused','completed'=>'Completed'] as $st=>$label)
                <option value="{{ $st }}" @selected(request('status')==$st)>{{ $label }}</option>
            @endforeach
        </select>
        @if($metaBusinesses->isNotEmpty())
            <select name="business_id" class="form-select" style="width:auto" onchange="this.form.submit()">
                <option value="">All businesses</option>
                @foreach($metaBusinesses as $business)
                    <option value="{{ $business->meta_business_id }}" @selected(request('business_id')==$business->meta_business_id)>{{ $business->meta_business_name ?: $business->meta_business_id }}</option>
                @endforeach
            </select>
        @endif
        <button class="btn btn-outline-soft">Search</button>
        @if(request()->hasAny(['search','status','business_id']))
            <a href="{{ route('crm.campaigns.index') }}">Clear</a>
        @endif
    </form>

    <div class="card-crm table-wrap">
        <table class="table-crm">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Channel</th>
                    <th>Leads</th>
                    <th>Assigned Agents</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($campaigns as $campaign)
                <tr>
                    <td>
                        <a href="{{ route('crm.campaigns.show', $campaign) }}" class="d-flex align-items-center gap-2 text-dark">
                            <div class="camp-thumb"><i class="bi bi-megaphone"></i></div>
                            <span>
                                <strong>{{ $campaign->name }}</strong>
                                <div class="small text-muted">{{ $campaign->metaBusinessLabel() ?: ($campaign->objective ?: 'Lead generation') }}</div>
                            </span>
                        </a>
                    </td>
                    <td><span class="d-inline-flex align-items-center gap-1">@include('components.source-icon', ['source'=>$campaign->source]) {{ $campaign->source->name ?? '—' }}</span></td>
                    <td>{{ number_format($campaign->total_leads) }}</td>
                    <td>
                        @if($campaign->users->isEmpty())
                            <span class="text-muted">None</span>
                        @else
                            <div class="assign-chip-stack">
                                @foreach($campaign->users->take(3) as $u)
                                    <span class="assign-chip" title="{{ $u->name }}">
                                        <span class="avatar" style="width:20px;height:20px;font-size:9px">{{ $u->initials() }}</span>
                                        {{ $u->name }}
                                    </span>
                                @endforeach
                                @if($campaign->users->count() > 3)
                                    <span class="small text-muted">+{{ $campaign->users->count() - 3 }}</span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td><span class="badge-soft {{ $campaign->status==='active'?'badge-active':($campaign->status==='paused'?'badge-paused':'badge-done') }}">{{ ucfirst($campaign->status) }}</span></td>
                    <td class="td-actions">
                        <a href="{{ route('crm.leads.index', ['campaigns' => [$campaign->id]]) }}" class="btn btn-primary btn-sm">View Leads</a>
                        @can('update', $campaign)
                            <button type="button" class="btn btn-outline-soft btn-sm"
                                data-assign-open
                                data-assign-url="{{ route('crm.campaigns.assign', $campaign) }}"
                                data-assign-name="{{ $campaign->name }}"
                                data-assign-users="{{ $campaign->users->pluck('id')->implode(',') }}">
                                Assign
                            </button>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-state">
                        @if(!empty($isScopedAgent))
                            No campaigns assigned to you yet. Ask an admin to assign one.
                        @else
                            No campaigns yet. Create one, then assign agents.
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="page-meta">Showing {{ $campaigns->firstItem() ?: 0 }} to {{ $campaigns->lastItem() ?: 0 }} of {{ $campaigns->total() }} campaigns</div>
            {{ $campaigns->links() }}
        </div>
    </div>
</div>
@include('crm.campaigns._assign-agents')
@endsection
