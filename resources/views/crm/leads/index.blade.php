@extends('layouts.crm')
@section('title', 'Lead Intelligence')
@section('content')
<div class="crm-topbar">
    <div>
        <p class="kicker">{{ now()->format('l · j F Y') }}</p>
        <h1 class="crm-title">{{ !empty($isScopedAgent) ? 'My Leads' : 'Leads' }}</h1>
        <p class="crm-subtitle">{{ !empty($isScopedAgent) ? 'Leads from campaigns assigned to you.' : 'Find a lead, open it, and follow up.' }}</p>
    </div>
    <div class="crm-actions">
        @can('leads.create')
            <a href="{{ route('crm.leads.create') }}" class="btn btn-primary">+ Add Lead</a>
        @endcan
        @can('leads.export')
            <a href="{{ route('crm.leads.export') }}" class="btn btn-outline-soft"><i class="bi bi-download"></i> Export</a>
        @endcan
    </div>
</div>

<div class="crm-content">
    @include('crm.leads._filters')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="tabs-bar">
            @php
                $leadTabs = !empty($isScopedAgent)
                    ? ['all'=>'All','mine'=>'Assigned to me','hot'=>'Hot']
                    : ['all'=>'All Leads','mine'=>'My Leads','hot'=>'Hot Leads','duplicates'=>'Duplicates','high'=>'High Intent'];
            @endphp
            @foreach($leadTabs as $key=>$label)
                <a class="{{ $tab===$key ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['tab'=>$key]) }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
            @endforeach
        </div>
        <form method="GET" class="search-box">
            @foreach(request()->except('search','page') as $k=>$v)
                @if(is_array($v))
                    @foreach($v as $item)
                        <input type="hidden" name="{{ $k }}[]" value="{{ $item }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach
            <i class="bi bi-search"></i>
            <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search leads by name, phone, email...">
        </form>
    </div>

    <div class="card-crm table-wrap">
        <table class="table-crm">
            <thead><tr>
                <th>Name</th><th>Phone</th><th>Campaign</th><th>Source</th><th>Interested In</th><th>Assigned Agent</th><th>Status</th><th>Action</th>
            </tr></thead>
            <tbody>
            @forelse($leads as $lead)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar">{{ $lead->initials() }}</div>
                            <strong>{{ $lead->full_name }}</strong>
                        </div>
                    </td>
                    <td>{{ $lead->phone ?: '—' }}</td>
                    <td>{{ $lead->campaign->name ?? '—' }}</td>
                    <td><span class="d-inline-flex align-items-center gap-1">@include('components.source-icon', ['source'=>$lead->source]) {{ $lead->source->name ?? '—' }}</span></td>
                    <td>{{ $lead->interested_in ?: '—' }}</td>
                    <td>
                        @if($lead->assignedUser)
                            <span class="d-inline-flex align-items-center gap-1"><span class="avatar" style="width:22px;height:22px;font-size:10px">{{ $lead->assignedUser->initials() }}</span>{{ $lead->assignedUser->name }}</span>
                        @else Unassigned @endif
                    </td>
                    <td><span class="badge-soft {{ $lead->statusBadgeClass() }}">{{ $lead->statusLabel() }}</span></td>
                    <td class="td-actions">
                        <a href="{{ route('crm.leads.show', $lead) }}" class="btn btn-primary btn-sm">View</a>
                        <a href="{{ route('crm.leads.activity', $lead) }}" class="btn btn-outline-soft btn-sm">Activity</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty-state">{{ !empty($isScopedAgent) ? 'No leads on your campaigns yet.' : 'No leads found.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="page-meta">{{ number_format($leads->total()) }} {{ ($filterState['hasActive'] ?? false) ? 'matching leads' : 'Total leads' }} · Showing {{ $leads->firstItem() ?: 0 }} to {{ $leads->lastItem() ?: 0 }}</div>
            {{ $leads->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/lead-filters.js') }}?v=20260817lf2"></script>
@endpush
