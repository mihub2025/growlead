@extends('layouts.super')
@section('title', 'Organizations')
@section('content')
<div class="crm-topbar">
    <div>
        <p class="kicker">Platform</p>
        <h1 class="crm-title d-inline">Organizations</h1>
        <p class="crm-subtitle">Every workspace on this deployment. Only you can create new ones.</p>
    </div>
    <div class="crm-actions">
        <form method="GET" class="search-box">
            <i class="bi bi-search"></i>
            <input name="search" value="{{ $search }}" class="form-control" placeholder="Search organizations...">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        </form>
        <a href="{{ route('super.organizations.create') }}" class="btn btn-primary">+ New organization</a>
    </div>
</div>
<div class="crm-content">
    <div class="tabs-bar mb-3">
        <a class="{{ $status === '' ? 'active' : '' }}" href="{{ route('super.organizations.index', array_filter(['search' => $search])) }}">All</a>
        <a class="{{ $status === 'active' ? 'active' : '' }}" href="{{ route('super.organizations.index', array_filter(['search' => $search, 'status' => 'active'])) }}">Active</a>
        <a class="{{ $status === 'suspended' ? 'active' : '' }}" href="{{ route('super.organizations.index', array_filter(['search' => $search, 'status' => 'suspended'])) }}">Suspended</a>
    </div>
    <div class="card-crm p-0">
        <div class="table-responsive">
            <table class="table table-crm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Organization</th>
                        <th>Industry</th>
                        <th>Status</th>
                        <th>Users</th>
                        <th>Leads</th>
                        <th>Campaigns</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($organizations as $org)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $org->name }}</div>
                                <div class="small text-muted">{{ $org->slug }}</div>
                            </td>
                            <td>{{ config('crm.industries.'.$org->industry, $org->industry) }}</td>
                            <td><span class="badge-soft">{{ ucfirst($org->status) }}</span></td>
                            <td>{{ $org->users_count }}</td>
                            <td>{{ $org->leads_count }}</td>
                            <td>{{ $org->campaigns_count }}</td>
                            <td class="small text-muted">{{ $org->created_at->format('j M Y') }}</td>
                            <td class="text-end"><a href="{{ route('super.organizations.show', $org) }}">Monitor</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted py-4 px-3">No organizations match this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $organizations->links() }}</div>
</div>
@endsection
