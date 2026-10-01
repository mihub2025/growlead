@extends('layouts.super')
@section('title', 'Platform')
@section('content')
<main class="page">
    <div class="head">
        <div>
            <p class="kicker">{{ now()->format('l · j F Y') }}</p>
            <h1 class="serif">Platform overview</h1>
            <p>Create organizations and monitor every workspace from here. Public signup is closed.</p>
        </div>
        <a href="{{ route('super.organizations.create') }}" class="primary">New organization</a>
    </div>

    <section class="kpis">
        @include('components.kpi-card', ['label' => 'Organizations', 'value' => $organizations, 'icon' => 'bi-building'])
        @include('components.kpi-card', ['label' => 'Active', 'value' => $active, 'icon' => 'bi-check2-circle'])
        @include('components.kpi-card', ['label' => 'Suspended', 'value' => $suspended, 'icon' => 'bi-pause-circle'])
        @include('components.kpi-card', ['label' => 'Workspace users', 'value' => $users, 'icon' => 'bi-people'])
        @include('components.kpi-card', ['label' => 'Leads', 'value' => $leads, 'icon' => 'bi-person-lines-fill'])
    </section>

    <div class="card-crm p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Recent organizations</h5>
            <a href="{{ route('super.organizations.index') }}">View all →</a>
        </div>
        <div class="table-responsive">
            <table class="table table-crm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Organization</th>
                        <th>Status</th>
                        <th>Users</th>
                        <th>Leads</th>
                        <th>Campaigns</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $org)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $org->name }}</div>
                                <div class="small text-muted">{{ config('crm.industries.'.$org->industry, $org->industry) }} · {{ $org->created_at->diffForHumans() }}</div>
                            </td>
                            <td><span class="badge-soft {{ $org->status === 'active' ? 'badge-score-mid' : '' }}">{{ ucfirst($org->status) }}</span></td>
                            <td>{{ $org->users_count }}</td>
                            <td>{{ $org->leads_count }}</td>
                            <td>{{ $org->campaigns_count }}</td>
                            <td class="text-end"><a href="{{ route('super.organizations.show', $org) }}">Monitor</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted py-4">No organizations yet. Create the first workspace.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>
@endsection
