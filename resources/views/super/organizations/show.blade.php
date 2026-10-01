@extends('layouts.super')
@section('title', $organization->name)
@section('content')
<div class="crm-topbar">
    <div>
        <p class="kicker">Monitor</p>
        <h1 class="crm-title d-inline">{{ $organization->name }}</h1>
        <p class="crm-subtitle">{{ config('crm.industries.'.$organization->industry, $organization->industry) }} · {{ $organization->currencyCode() }} · {{ $organization->timezone }}</p>
    </div>
    <div class="crm-actions">
        <form method="POST" action="{{ route('super.organizations.status', $organization) }}">
            @csrf
            @method('PUT')
            @if($organization->status === 'active')
                <input type="hidden" name="status" value="suspended">
                <button class="btn btn-outline-soft" onclick="return confirm('Suspend this organization? Its users will not be able to sign in.')">Suspend</button>
            @else
                <input type="hidden" name="status" value="active">
                <button class="btn btn-primary">Activate</button>
            @endif
        </form>
    </div>
</div>
<div class="crm-content">
    <div class="row g-3 mb-3">
        <div class="col">@include('components.kpi-card', ['label'=>'Users','value'=>$organization->users_count,'icon'=>'bi-people'])</div>
        <div class="col">@include('components.kpi-card', ['label'=>'Active users','value'=>$active_users,'icon'=>'bi-person-check'])</div>
        <div class="col">@include('components.kpi-card', ['label'=>'Leads','value'=>$organization->leads_count,'icon'=>'bi-person-lines-fill'])</div>
        <div class="col">@include('components.kpi-card', ['label'=>'Campaigns','value'=>$organization->campaigns_count,'icon'=>'bi-megaphone'])</div>
    </div>

    <div class="card-crm p-3 mb-3">
        <h5 class="mb-3">Workspace</h5>
        <div class="row g-3">
            <div class="col-md-3"><div class="text-muted small">Status</div><strong>{{ ucfirst($organization->status) }}</strong></div>
            <div class="col-md-3"><div class="text-muted small">Created</div><strong>{{ $organization->created_at->format('j M Y H:i') }}</strong></div>
            <div class="col-md-3"><div class="text-muted small">Created by</div><strong>{{ $organization->creator->name ?? '—' }}</strong></div>
            <div class="col-md-3"><div class="text-muted small">Country</div><strong>{{ $organization->country ?: '—' }}</strong></div>
        </div>
        @if($admins->isNotEmpty())
            <div class="mt-3">
                <div class="text-muted small mb-1">Administrators</div>
                @foreach($admins as $admin)
                    <div>{{ $admin->name }} · {{ $admin->email }}</div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card-crm p-3 h-100">
                <h5 class="mb-3">Recent users</h5>
                @forelse($users as $user)
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <div>
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <div class="small text-muted">{{ $user->email }} · {{ $user->roleName() }}</div>
                        </div>
                        <span class="small text-muted">{{ ucfirst($user->status) }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No users yet.</p>
                @endforelse
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-crm p-3 h-100">
                <h5 class="mb-3">Recent leads</h5>
                @forelse($leads as $lead)
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <div>
                            <div class="fw-semibold">{{ $lead->full_name ?? $lead->name ?? 'Lead #'.$lead->id }}</div>
                            <div class="small text-muted">{{ $lead->email ?: $lead->phone ?: 'No contact' }}</div>
                        </div>
                        <span class="small text-muted">{{ $lead->created_at?->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No leads yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
