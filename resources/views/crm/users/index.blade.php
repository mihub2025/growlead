@extends('layouts.crm')
@section('title', 'Users & Teams')
@section('content')
<div class="crm-topbar">
    <div>
        <p class="kicker">{{ now()->format('l · j F Y') }}</p>
        <h1 class="crm-title d-inline">Users & Teams</h1>
        <p class="crm-subtitle">Manage your team members, roles, and access permissions.</p>
    </div>
    <div class="crm-actions">
        <form method="GET" class="search-box"><i class="bi bi-search"></i><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search users by name, email or role..."></form>
        <button class="btn btn-yellow" data-bs-toggle="offcanvas" data-bs-target="#inviteUser">+ Add New User</button>
    </div>
</div>
<div class="crm-content">
    <div class="row g-3 mb-3">
        <div class="col">@include('components.kpi-card', ['label'=>'Total Users','value'=>$stats['total'],'icon'=>'bi-people','color'=>'blue','change'=>0])</div>
        <div class="col">@include('components.kpi-card', ['label'=>'Active Agents','value'=>$stats['agents'],'icon'=>'bi-person-check','color'=>'green','change'=>0])</div>
        <div class="col">@include('components.kpi-card', ['label'=>'Managers','value'=>$stats['managers'],'icon'=>'bi-briefcase','color'=>'purple','change'=>0])</div>
        <div class="col">@include('components.kpi-card', ['label'=>'Average SLA','value'=>$stats['sla'].'m','icon'=>'bi-stopwatch','color'=>'orange','change'=>0])</div>
    </div>
    <div class="tabs-bar mb-3">
        @foreach(['active'=>'Active','paused'=>'Paused','archived'=>'Archived','invited'=>'Invited','teams'=>'Teams'] as $key=>$label)
            <a class="{{ $tab===$key?'active':'' }}" href="{{ route('crm.users.index', ['tab'=>$key]) }}">{{ $label }} ({{ $tabCounts[$key] ?? 0 }})</a>
        @endforeach
    </div>
    @if($tab==='teams')
        <div class="card-crm p-3 mb-3">
            <div class="fw-bold mb-2">Create a team</div>
            <form method="POST" action="{{ route('crm.teams.store') }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-3"><label class="form-label">Team name</label><input name="name" class="form-control" placeholder="Team name" required></div>
                    <div class="col-md-3"><label class="form-label">Description</label><input name="description" class="form-control" placeholder="Description"></div>
                    <div class="col-md-3">
                        <label class="form-label">Manager</label>
                        <select name="manager_id" class="form-select">
                            <option value="">No manager</option>
                            @foreach($orgUsers as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><button class="btn btn-primary w-100">Add Team</button></div>
                </div>
                <div class="mt-3">
                    <div class="form-label">Members</div>
                    <div class="team-picker">
                        @forelse($orgUsers as $member)
                            <label class="team-picker-item">
                                <input type="checkbox" name="user_ids[]" value="{{ $member->id }}">
                                <span class="avatar">{{ $member->initials() }}</span>
                                <span>{{ $member->name }}</span>
                            </label>
                        @empty
                            <div class="text-muted small">Invite users first, then add them here.</div>
                        @endforelse
                    </div>
                </div>
            </form>
        </div>
        @forelse($teamList as $team)
            <div class="card-crm p-3 mb-2">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <div class="fw-bold">{{ $team->name }}</div>
                        <div class="small text-muted">{{ $team->users->count() }} members · Manager: {{ $team->manager->name ?? '—' }}@if($team->description) · {{ $team->description }}@endif</div>
                        <div class="team-chips mt-2">
                            @forelse($team->users as $member)
                                <span class="team-chip"><span class="avatar">{{ $member->initials() }}</span>{{ $member->name }}@if($team->manager_id===$member->id)<em>Manager</em>@endif</span>
                            @empty
                                <span class="text-muted small">No members yet.</span>
                            @endforelse
                        </div>
                    </div>
                    <button class="btn btn-outline-soft btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#team-edit-{{ $team->id }}" aria-expanded="false">Manage members</button>
                </div>
                <div class="collapse mt-3" id="team-edit-{{ $team->id }}">
                    <form method="POST" action="{{ route('crm.teams.update', $team) }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-2">
                            <div class="col-md-4"><label class="form-label">Team name</label><input name="name" class="form-control" value="{{ $team->name }}" required></div>
                            <div class="col-md-4"><label class="form-label">Description</label><input name="description" class="form-control" value="{{ $team->description }}"></div>
                            <div class="col-md-4">
                                <label class="form-label">Manager</label>
                                <select name="manager_id" class="form-select">
                                    <option value="">No manager</option>
                                    @foreach($orgUsers as $member)
                                        <option value="{{ $member->id }}" @selected($team->manager_id==$member->id)>{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-label mt-3">Members</div>
                        <div class="team-picker">
                            @foreach($orgUsers as $member)
                                <label class="team-picker-item">
                                    <input type="checkbox" name="user_ids[]" value="{{ $member->id }}" @checked($team->users->contains('id', $member->id) || $team->manager_id==$member->id)>
                                    <span class="avatar">{{ $member->initials() }}</span>
                                    <span>{{ $member->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-outline-soft" data-bs-toggle="collapse" data-bs-target="#team-edit-{{ $team->id }}">Cancel</button>
                            <button class="btn btn-primary">Save team</button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="card-crm p-4 text-muted">No teams yet. Create one above and assign members.</div>
        @endforelse
    @else
    <div class="card-crm table-wrap">
                <table class="table-crm">
            <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Team</th><th>Campaigns Assigned</th><th>Productivity Score</th><th>Response Rate</th><th>Last Active</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $user)
                @php $score = min(99, 55 + ($user->assignedLeads()->count() % 40)); @endphp
                <tr>
                    <td><div class="d-flex align-items-center gap-2"><div class="avatar">{{ $user->initials() }}</div><strong>{{ $user->name }}</strong></div></td>
                    <td>{{ $user->email }}</td>
                    <td><span class="badge-soft role-{{ $user->roleSlug() }}">{{ $user->roleName() }}</span></td>
                    <td>{{ $user->teams->pluck('name')->join(', ') ?: '—' }}</td>
                    <td>{{ $user->campaigns()->count() }}</td>
                    <td><div class="prod-ring {{ $score>=80?'':($score>=65?'mid':'low') }}" style="--p: {{ $score * 3.6 }}deg"><span>{{ $score }}</span></div></td>
                    <td>{{ min(99, 60 + ($score % 30)) }}%</td>
                    <td>{{ optional($user->last_active_at)->diffForHumans() ?: '—' }}</td>
                    <td><span class="badge-soft badge-active">{{ ucfirst($user->status) }}</span></td>
                    <td><i class="bi bi-three-dots-vertical text-muted"></i></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="page-meta">Showing {{ $users->firstItem() ?: 0 }} to {{ $users->lastItem() ?: 0 }} of {{ $users->total() }} users</div>
            {{ $users->links() }}
        </div>
    </div>
    @endif
</div>
<div class="offcanvas offcanvas-end" id="inviteUser">
    <div class="offcanvas-header"><h5>Invite New User</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
    <div class="offcanvas-body">
        <p class="text-muted small">Send an invitation to a new team member.</p>
        <form method="POST" action="{{ route('crm.users.store') }}">
            @csrf
            <div class="mb-2"><label class="form-label">Full Name</label><input name="name" class="form-control" required></div>
            <div class="mb-2"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" required></div>
            <div class="mb-2"><label class="form-label">Role</label><select name="role_id" class="form-select">@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></div>
            <div class="mb-2"><label class="form-label">Team</label><select name="team_id" class="form-select"><option value="">None</option>@foreach($teams as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach</select></div>
            <div class="mb-2"><label class="form-label">Message (Optional)</label><textarea name="message" class="form-control" maxlength="250" placeholder="You have been invited to join GrowLead CRM."></textarea></div>
            <div class="mb-3">
                <div class="form-label">Access Permissions</div>
                <label class="form-check"><input type="checkbox" class="form-check-input" checked disabled> Dashboard Access</label>
                <label class="form-check"><input type="checkbox" class="form-check-input" checked disabled> Leads Access</label>
                <label class="form-check"><input type="checkbox" class="form-check-input" checked disabled> Campaigns Management</label>
                <label class="form-check"><input type="checkbox" class="form-check-input" checked disabled> Reports Access</label>
                <label class="form-check"><input type="checkbox" class="form-check-input" disabled> Settings Access</label>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-soft flex-grow-1" data-bs-dismiss="offcanvas">Cancel</button>
                <button class="btn btn-yellow flex-grow-1"><i class="bi bi-send"></i> Send Invitation</button>
            </div>
        </form>
    </div>
</div>
@endsection
