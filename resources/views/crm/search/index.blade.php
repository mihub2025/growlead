@extends('layouts.crm')
@section('title', 'Search')
@section('content')
@include('components.page-header', ['title' => 'Search', 'subtitle' => 'Find leads, campaigns and users.'])
<div class="crm-content">
    <form class="mb-3" method="GET"><input name="q" value="{{ $q }}" class="form-control" placeholder="Search..."></form>
    <div class="row g-3">
        <div class="col-md-4"><div class="card-crm p-3"><h5>Leads</h5>@forelse($leads as $lead)<div><a href="{{ route('crm.leads.show', $lead) }}">{{ $lead->full_name }}</a></div>@empty<p class="text-muted">No leads</p>@endforelse</div></div>
        <div class="col-md-4"><div class="card-crm p-3"><h5>Campaigns</h5>@forelse($campaigns as $campaign)<div><a href="{{ route('crm.campaigns.show', $campaign) }}">{{ $campaign->name }}</a></div>@empty<p class="text-muted">No campaigns</p>@endforelse</div></div>
        <div class="col-md-4"><div class="card-crm p-3"><h5>Users</h5>@forelse($users as $user)<div>{{ $user->name }}</div>@empty<p class="text-muted">No users</p>@endforelse</div></div>
    </div>
</div>
@endsection
