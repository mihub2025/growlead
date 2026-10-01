@extends('layouts.crm')
@section('title', 'Settings')
@section('content')
@php
    $tabs = [
        'organization' => 'Organization',
        'users' => 'Users & Teams',
        'integrations' => 'Integrations',
        'routing' => 'Lead Routing',
        'automations' => 'Automations',
        'fields' => 'Custom Fields',
        'duplicates' => 'Duplicates',
        'permissions' => 'Permissions',
    ];
    $autoMeta = [
        'lead_created' => ['bi-person-plus-fill', 'Automatically assign and follow up when a new lead arrives.'],
        'lead_assigned' => ['bi-geo-alt-fill', 'Notify the owner and start the first-contact workflow.'],
        'stage_changed' => ['bi-diagram-3-fill', 'Trigger the next action when a lead moves stages.'],
        'status_changed' => ['bi-arrow-repeat', 'Keep the team in sync when lead status changes.'],
        'task_overdue' => ['bi-clock-history', 'Alert the team before SLA or follow-up deadlines slip.'],
        'campaign_lead_received' => ['bi-megaphone-fill', 'Handle inbound campaign leads the moment they land.'],
    ];
    $gaugeC = 339.3;
    $gaugeArc = 254.47;
    $gaugeOffset = $gaugeArc * (1 - ($efficiency['percent'] / 100));
@endphp
<div class="settings-page">
    <div class="crm-topbar">
        <div>
            <p class="kicker">Workspace</p>
            <h1 class="crm-title d-inline">Settings & Automation</h1>
            <p class="crm-subtitle">Manage your organization, users, integrations, automations and data governance — all in one place.</p>
        </div>
        <div class="crm-actions">
            <a href="{{ route('crm.settings.index') }}" class="settings-help"><span>Need help?</span> <span class="q">?</span></a>
        </div>
    </div>

    <div class="crm-content">
        <nav class="settings-tabs" aria-label="Settings sections">
            @foreach($tabs as $key => $label)
                <a class="{{ $tab === $key ? 'active' : '' }}" href="{{ route('crm.settings.index', ['tab' => $key]) }}">{{ $label }}</a>
            @endforeach
        </nav>

        @if($tab === 'organization')
            <div class="settings-kpis">
                @foreach($kpis as $kpi)
                    <div class="card-crm set-kpi">
                        <div class="set-kpi-top">
                            <div class="set-kpi-ico"><i class="bi {{ $kpi['icon'] }}"></i></div>
                            <div class="set-kpi-label">{{ $kpi['label'] }}</div>
                        </div>
                        <div class="set-kpi-value">{{ $kpi['value'] }}</div>
                        <div class="set-kpi-trend {{ $kpi['change'] < 0 ? 'down' : '' }}">
                            {{ $kpi['change'] >= 0 ? '↑' : '↓' }} {{ abs($kpi['change']) }}% vs last 30 days
                        </div>
                    </div>
                @endforeach
                <div class="card-crm set-eff">
                    <div class="set-eff-head">
                        <h5>Automation Efficiency</h5>
                        <span class="badge-soft badge-score-mid">Beta</span>
                    </div>
                    <div class="set-eff-body">
                        <div class="eff-gauge">
                            <svg viewBox="0 0 120 120" aria-hidden="true">
                                <circle class="track" cx="60" cy="60" r="54" stroke-dasharray="{{ $gaugeArc }} {{ $gaugeC }}"></circle>
                                <circle class="bar" cx="60" cy="60" r="54" stroke-dasharray="{{ $gaugeArc }} {{ $gaugeC }}" stroke-dashoffset="{{ $gaugeOffset }}"></circle>
                            </svg>
                            <div class="eff-center"><strong>{{ $efficiency['percent'] }}%</strong><span>Efficiency</span></div>
                        </div>
                        <div class="eff-stats">
                            <div class="eff-stat"><span class="eff-dot" style="background:#161513"></span><span><b>{{ number_format($efficiency['tasks']) }}</b> Tasks automated</span></div>
                            <div class="eff-stat"><span class="eff-dot" style="background:#9a7b3c"></span><span><b>{{ number_format($efficiency['hours']) }}</b> Hours saved</span></div>
                            <div class="eff-stat"><span class="eff-dot" style="background:#16a34a"></span><span><b>{{ $efficiency['pipeline'] }}</b> Pipeline influenced</span></div>
                            <div class="eff-trend">+ {{ $efficiency['change'] }}% vs last 30 days</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-grid">
                <div class="card-crm settings-card set-integrations">
                    <div class="card-head">
                        <h5>Connected Integrations</h5>
                        <a href="{{ route('crm.settings.index', ['tab' => 'integrations']) }}">View all integrations →</a>
                    </div>
                    <div class="int-grid">
                        @foreach($integrationCatalog as $item)
                            <div class="int-tile-set">
                                <div class="int-tile-top">
                                    @include('components.source-icon', ['slug' => $item['provider']])
                                    <strong class="text-truncate">{{ $item['name'] }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="int-status {{ $item['connected'] ? '' : 'off' }}"><span class="dot {{ $item['connected'] ? 'ok' : 'warn' }}"></span> {{ $item['connected'] ? 'Connected' : 'Not connected' }}</span>
                                    <span class="int-meta {{ $item['connected'] ? 'on' : 'off' }}">{{ $item['category'] }} • {{ $item['connected'] ? 'Active' : 'Idle' }}</span>
                                </div>
                            </div>
                        @endforeach
                        <a href="{{ route('crm.integrations.index') }}" class="int-tile-set add">
                            <i class="bi bi-plus-lg"></i>
                            Add Integration
                        </a>
                    </div>
                </div>

                <div class="card-crm settings-card set-automations">
                    <div class="card-head">
                        <h5>Automation Rules</h5>
                        <a href="{{ route('crm.settings.index', ['tab' => 'automations']) }}">View all rules →</a>
                    </div>
                    @forelse($automations->take(4) as $rule)
                        @php
                            $meta = $autoMeta[$rule->trigger] ?? ['bi-lightning-charge-fill', 'Run this rule whenever the trigger condition is met.'];
                            $hay = strtolower($rule->name.' '.$rule->trigger);
                            if (str_contains($hay, 'duplicate')) $meta = ['bi-files', 'Flag possible duplicates before they clutter the pipeline.'];
                            if (str_contains($hay, 'score')) $meta = ['bi-stars', 'Score inbound leads so the team works the best opportunities first.'];
                            if (str_contains($hay, 'sla')) $meta = ['bi-clock-history', 'Alert the assigned agent before response SLAs are breached.'];
                            if (str_contains($hay, 'assign') || str_contains($hay, 'location')) $meta = ['bi-geo-alt-fill', 'Automatically assign leads by source, location, and availability.'];
                            $on = $rule->status === 'active';
                        @endphp
                        <div class="auto-row">
                            <div class="auto-ico"><i class="bi {{ $meta[0] }}"></i></div>
                            <div>
                                <strong>{{ $rule->name }}</strong>
                                <p>{{ $meta[1] }}</p>
                            </div>
                            <div class="auto-runs"><b>{{ number_format($rule->runs_count) }}</b> Runs</div>
                            <div class="auto-state {{ $on ? '' : 'off' }}">{{ $on ? 'Active' : ucfirst($rule->status) }}</div>
                            <form method="POST" action="{{ route('crm.settings.automations.toggle', $rule) }}">
                                @csrf @method('PUT')
                                <label class="switch mb-0">
                                    <input type="checkbox" @checked($on) @disabled(! $canManageAutomations) onchange="this.form.submit()">
                                    <span class="slider"></span>
                                </label>
                            </form>
                        </div>
                    @empty
                        <div class="empty-state py-4">No automations yet. <a href="{{ route('crm.automations.index') }}">Create a rule →</a></div>
                    @endforelse
                </div>

                <div class="card-crm settings-card set-pipeline">
                    <div class="card-head">
                        <h5>Pipeline & Custom Fields</h5>
                        <a href="{{ route('crm.settings.index', ['tab' => 'fields']) }}">Manage fields →</a>
                    </div>
                    <div class="pipe-label">Active Pipeline</div>
                    <select class="form-select pipe-select" onchange="location.href=this.value">
                        @forelse($pipelines as $pipeline)
                            <option value="{{ route('crm.settings.index', ['tab' => 'organization', 'pipeline_id' => $pipeline->id]) }}" @selected(optional($activePipeline)->id == $pipeline->id)>{{ $pipeline->name }}</option>
                        @empty
                            <option>No pipeline</option>
                        @endforelse
                    </select>
                    <div class="pipe-flow">
                        @foreach($pipelineStages as $stage)
                            <div class="pipe-stage" style="background: {{ $stage['bg'] }}">
                                <b>{{ $stage['name'] }}</b>
                                <small>{{ $stage['percent'] }}%</small>
                            </div>
                        @endforeach
                    </div>
                    <div class="pipe-label mt-4">Top Custom Fields</div>
                    <div class="field-row">
                        @foreach($topFields as $field)
                            <div class="field-chip">
                                <strong>{{ $field->name }}</strong>
                                <span>{{ ucfirst($field->type) }}</span>
                            </div>
                        @endforeach
                        <a href="{{ route('crm.settings.index', ['tab' => 'fields']) }}" class="field-add">+ Add Field</a>
                    </div>
                </div>

                <div class="card-crm settings-card set-duplicates">
                    <div class="card-head">
                        <h5>Duplicate Handling</h5>
                        <a href="{{ route('crm.settings.index', ['tab' => 'duplicates']) }}">Manage rules →</a>
                    </div>
                    <form method="POST" action="{{ route('crm.settings.duplicates') }}">
                        @csrf @method('PUT')
                        @foreach([
                            ['duplicate_detection', 'bi-search', 'Duplicate detection', 'Scan new leads for matching phone, email, or external IDs.', true],
                            ['merge_suggestions', 'bi-intersect', 'Merge suggestions', 'Queue likely matches for review instead of creating extra records.', true],
                            ['auto_merge', 'bi-link-45deg', 'Auto-merge', 'Automatically merge high-confidence duplicates without review.', false],
                        ] as $row)
                            <div class="dup-row">
                                <div class="dup-ico"><i class="bi {{ $row[1] }}"></i></div>
                                <div>
                                    <strong>{{ $row[2] }}</strong>
                                    <p>{{ $row[3] }}</p>
                                </div>
                                <div class="auto-state {{ data_get($org->settings, $row[0], $row[4]) ? '' : 'off' }}">{{ data_get($org->settings, $row[0], $row[4]) ? 'Active' : 'Off' }}</div>
                                <label class="switch mb-0">
                                    <input type="hidden" name="{{ $row[0] }}" value="0">
                                    <input type="checkbox" name="{{ $row[0] }}" value="1" @checked(data_get($org->settings, $row[0], $row[4])) @disabled(! $canManage) onchange="this.form.submit()">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        @endforeach
                    </form>
                    <div class="dup-foot">
                        <div class="dup-stat"><small>Duplicate Rate</small><b>{{ $duplicateStats['rate'] }}%</b> <em class="{{ $duplicateStats['rate_label'] === 'Low' ? 'low' : ($duplicateStats['rate_label'] === 'Medium' ? 'mid' : 'high') }}">{{ $duplicateStats['rate_label'] }}</em></div>
                        <div class="dup-stat"><small>Records Reviewed</small><b>{{ number_format($duplicateStats['reviewed']) }}</b> <span class="text-muted small">This Month</span></div>
                        <div class="dup-stat"><small>Duplicates Merged</small><b>{{ number_format($duplicateStats['merged']) }}</b> <span class="text-muted small">This Month</span></div>
                    </div>
                </div>

                <div class="card-crm settings-card set-permissions">
                    <div class="card-head">
                        <h5>Permissions Overview</h5>
                        <a href="{{ route('crm.settings.index', ['tab' => 'permissions']) }}">Manage roles →</a>
                    </div>
                    <div class="perm-wrap">
                        <table class="perm-table">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    @foreach($overviewRoles as $role)<th>{{ $role->name }}</th>@endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permissionModules as $label => $slug)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        @foreach($overviewRoles as $role)
                                            <td>
                                                @if($role->permissions->contains('slug', $slug))
                                                    <i class="bi bi-check-lg yes"></i>
                                                @else
                                                    <span class="no">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="perm-foot">
                        <span>Total Roles: {{ $roles->count() }}</span>
                        <a href="{{ route('crm.settings.index', ['tab' => 'permissions']) }}">View all permissions →</a>
                    </div>
                </div>
            </div>

            <div class="card-crm settings-form-card mb-3">
                <h5 class="mb-3">Organization Profile</h5>
                <form method="POST" action="{{ route('crm.settings.organization') }}" class="row g-3">
                    @csrf @method('PUT')
                    <div class="col-md-6"><label class="form-label">Organization Name</label><input name="name" value="{{ $org->name }}" class="form-control" required @disabled(! $canManage)></div>
                    <div class="col-md-6"><label class="form-label">Industry</label>
                        <select name="industry" class="form-select" @disabled(! $canManage)>@foreach(config('crm.industries') as $k=>$v)<option value="{{ $k }}" @selected($org->industry==$k)>{{ $v }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Currency</label>
                        @include('components.currency-select', ['value' => $org->currencyCode(), 'disabled' => ! $canManage])
                        <div class="form-text">Used everywhere: campaigns, leads, reports, and dashboards.</div>
                    </div>
                    <div class="col-md-3"><label class="form-label">Country</label><input name="country" value="{{ $org->country }}" class="form-control" @disabled(! $canManage)></div>
                    <div class="col-md-3"><label class="form-label">Timezone</label><input name="timezone" value="{{ $org->timezone }}" class="form-control" @disabled(! $canManage)></div>
                    <div class="col-md-3"><label class="form-label">Language</label><input name="language" value="{{ $org->language }}" class="form-control" @disabled(! $canManage)></div>
                    <div class="col-md-3"><label class="form-label">Phone Country</label><input name="phone_country" value="{{ $org->phone_country }}" class="form-control" @disabled(! $canManage)></div>
                    <div class="col-md-3"><label class="form-label">Date Format</label><input name="date_format" value="{{ $org->date_format }}" class="form-control" @disabled(! $canManage)></div>
                    <div class="col-md-3"><label class="form-label">SLA Minutes</label><input name="sla_minutes" value="{{ $org->sla_minutes }}" class="form-control" @disabled(! $canManage)></div>
                    @if($canManage)<div class="col-12"><button class="btn btn-primary">Save Organization</button></div>@endif
                </form>
            </div>

            <div class="card-crm settings-form-card">
                <h5 class="mb-3">Pipelines</h5>
                @if($canManage)
                    <form method="POST" action="{{ route('crm.settings.pipelines') }}" class="d-flex flex-wrap gap-2 mb-3">@csrf<input name="name" class="form-control" placeholder="New pipeline" style="max-width:280px"><button class="btn btn-outline-soft">Add</button></form>
                @endif
                @foreach($pipelines as $pipeline)
                    <div class="mb-3">
                        <strong>{{ $pipeline->name }}</strong>
                        <div class="d-flex flex-wrap gap-1 my-2">
                            @foreach($pipeline->stages as $stage)<span class="tag-pill" style="background:{{ $stage->color }}20;color:{{ $stage->color }}">{{ $stage->name }}</span>@endforeach
                        </div>
                        @if($canManage)
                            <form method="POST" action="{{ route('crm.settings.stages', $pipeline) }}" class="d-flex flex-wrap gap-2">@csrf<input name="name" class="form-control" placeholder="Stage name" style="max-width:220px"><input name="color" class="form-control" value="#4F46E5" style="max-width:120px"><button class="btn btn-sm btn-outline-soft">Add stage</button></form>
                        @endif
                    </div>
                @endforeach
            </div>

        @elseif($tab === 'users')
            <div class="settings-kpis simple">
                <div class="card-crm set-kpi"><div class="set-kpi-top"><div class="set-kpi-ico"><i class="bi bi-people"></i></div><div class="set-kpi-label">Total Users</div></div><div class="set-kpi-value">{{ $users->count() }}</div></div>
                <div class="card-crm set-kpi"><div class="set-kpi-top"><div class="set-kpi-ico"><i class="bi bi-diagram-3"></i></div><div class="set-kpi-label">Total Teams</div></div><div class="set-kpi-value">{{ $teams->count() }}</div></div>
                <div class="card-crm set-kpi"><div class="set-kpi-top"><div class="set-kpi-ico"><i class="bi bi-shield-check"></i></div><div class="set-kpi-label">Roles</div></div><div class="set-kpi-value">{{ $roles->count() }}</div></div>
                <div class="card-crm set-kpi"><div class="set-kpi-top"><div class="set-kpi-ico"><i class="bi bi-person-check"></i></div><div class="set-kpi-label">Active Users</div></div><div class="set-kpi-value">{{ $users->where('status','active')->count() }}</div></div>
            </div>
            <div class="card-crm settings-form-card">
                <div class="card-head"><h5>Users & Teams</h5><a href="{{ route('crm.users.index') }}">Open full directory →</a></div>
                <p class="text-muted mb-3">Invite teammates, assign roles, and organize people into teams from the Users module.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('crm.users.index') }}" class="btn btn-primary">Manage Users</a>
                    <a href="{{ route('crm.users.index', ['tab' => 'teams']) }}" class="btn btn-outline-soft">Manage Teams</a>
                </div>
            </div>

        @elseif($tab === 'integrations')
            <div class="card-crm settings-card mb-3">
                <div class="card-head">
                    <h5>Connected Integrations</h5>
                    <a href="{{ route('crm.integrations.index') }}">Configure credentials →</a>
                </div>
                <div class="int-grid wide">
                    @foreach($integrationCatalog as $item)
                        <div class="int-tile-set">
                            <div class="int-tile-top">
                                @include('components.source-icon', ['slug' => $item['provider']])
                                <strong class="text-truncate">{{ $item['name'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="int-status {{ $item['connected'] ? '' : 'off' }}"><span class="dot {{ $item['connected'] ? 'ok' : 'warn' }}"></span> {{ $item['connected'] ? 'Connected' : 'Not connected' }}</span>
                                <span class="int-meta {{ $item['connected'] ? 'on' : 'off' }}">{{ $item['category'] }} • {{ $item['connected'] ? 'Active' : 'Idle' }}</span>
                            </div>
                        </div>
                    @endforeach
                    <a href="{{ route('crm.integrations.index') }}" class="int-tile-set add"><i class="bi bi-plus-lg"></i> Add Integration</a>
                </div>
            </div>

        @elseif($tab === 'routing')
            <div class="card-crm settings-form-card">
                <div class="card-head"><h5>Lead Routing</h5></div>
                <p class="text-muted mb-3">Choose the default assignment method for new leads. Campaigns can still override this per campaign.</p>
                <form method="POST" action="{{ route('crm.settings.routing') }}">
                    @csrf @method('PUT')
                    <div class="routing-grid mb-3">
                        @foreach($routingMethods as $key => $info)
                            <label class="routing-card {{ $routingMethod === $key ? 'active' : '' }}">
                                <input type="radio" name="default_routing_method" value="{{ $key }}" class="d-none" @checked($routingMethod === $key) @disabled(! $canManage)>
                                <i class="bi {{ $info[2] }} mb-2 d-block"></i>
                                <strong class="d-block mb-1">{{ $info[0] }}</strong>
                                <span class="text-muted small">{{ $info[1] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @if($canManage)<button class="btn btn-primary">Save Routing</button>@endif
                </form>
            </div>

        @elseif($tab === 'automations')
            <div class="card-crm settings-card">
                <div class="card-head">
                    <h5>Automation Rules</h5>
                    <a href="{{ route('crm.automations.index') }}">Open automations →</a>
                </div>
                @forelse($automations as $rule)
                    @php
                        $meta = $autoMeta[$rule->trigger] ?? ['bi-lightning-charge-fill', 'Run this rule whenever the trigger condition is met.'];
                        $on = $rule->status === 'active';
                    @endphp
                    <div class="auto-row">
                        <div class="auto-ico"><i class="bi {{ $meta[0] }}"></i></div>
                        <div>
                            <strong>{{ $rule->name }}</strong>
                            <p>{{ $meta[1] }}</p>
                        </div>
                        <div class="auto-runs"><b>{{ number_format($rule->runs_count) }}</b> Runs</div>
                        <div class="auto-state {{ $on ? '' : 'off' }}">{{ $on ? 'Active' : ucfirst($rule->status) }}</div>
                        <form method="POST" action="{{ route('crm.settings.automations.toggle', $rule) }}">
                            @csrf @method('PUT')
                            <label class="switch mb-0">
                                <input type="checkbox" @checked($on) @disabled(! $canManageAutomations) onchange="this.form.submit()">
                                <span class="slider"></span>
                            </label>
                        </form>
                    </div>
                @empty
                    <div class="empty-state">No automations yet. <a href="{{ route('crm.automations.index') }}">Create a rule →</a></div>
                @endforelse
            </div>

        @elseif($tab === 'fields')
            <div class="card-crm settings-form-card mb-3">
                <div class="card-head"><h5>Custom Fields</h5></div>
                @if($canManage)
                    <form method="POST" action="{{ route('crm.settings.fields') }}" class="row g-2 mb-3">@csrf
                        <div class="col-md-3"><input name="name" class="form-control" placeholder="Field name" required></div>
                        <div class="col-md-2"><select name="entity_type" class="form-select"><option>lead</option><option>campaign</option><option>opportunity</option><option>offering</option></select></div>
                        <div class="col-md-2"><select name="type" class="form-select">@foreach(['text','textarea','number','currency','dropdown','date'] as $t)<option>{{ $t }}</option>@endforeach</select></div>
                        <div class="col-md-2"><button class="btn btn-primary">Add Field</button></div>
                    </form>
                @endif
                <div class="field-row">
                    @forelse($fields as $field)
                        <div class="field-chip">
                            <strong>{{ $field->name }}</strong>
                            <span>{{ $field->entity_type }} · {{ ucfirst($field->type) }}</span>
                        </div>
                    @empty
                        <div class="text-muted">No custom fields yet. Add one to capture extra lead or deal data.</div>
                    @endforelse
                </div>
            </div>
            <div class="card-crm settings-form-card">
                <h5 class="mb-3">Tags</h5>
                @if($canManage)
                    <form method="POST" action="{{ route('crm.settings.tags') }}" class="d-flex flex-wrap gap-2 mb-2 align-items-center">
                        @csrf
                        <input name="name" class="form-control" placeholder="Tag" style="max-width:220px">
                        @include('components.color-picker', ['name' => 'color', 'value' => '#4F46E5'])
                        <button class="btn btn-outline-soft">Add</button>
                    </form>
                @endif
                @foreach($tags as $tag)<span class="tag-pill me-1" style="background:{{ $tag->color }}20;color:{{ $tag->color }}">{{ $tag->name }}</span>@endforeach
            </div>

        @elseif($tab === 'duplicates')
            <div class="card-crm settings-card">
                <div class="card-head">
                    <h5>Duplicate Handling</h5>
                    <a href="{{ route('crm.leads.duplicates') }}">Review queue →</a>
                </div>
                <form method="POST" action="{{ route('crm.settings.duplicates') }}">
                    @csrf @method('PUT')
                    @foreach([
                        ['duplicate_detection', 'bi-search', 'Duplicate detection', 'Scan new leads for matching phone, email, or external IDs.', true],
                        ['merge_suggestions', 'bi-intersect', 'Merge suggestions', 'Queue likely matches for review instead of creating extra records.', true],
                        ['auto_merge', 'bi-link-45deg', 'Auto-merge', 'Automatically merge high-confidence duplicates without review.', false],
                    ] as $row)
                        <div class="dup-row">
                            <div class="dup-ico"><i class="bi {{ $row[1] }}"></i></div>
                            <div>
                                <strong>{{ $row[2] }}</strong>
                                <p>{{ $row[3] }}</p>
                            </div>
                            <div class="auto-state {{ data_get($org->settings, $row[0], $row[4]) ? '' : 'off' }}">{{ data_get($org->settings, $row[0], $row[4]) ? 'Active' : 'Off' }}</div>
                            <label class="switch mb-0">
                                <input type="hidden" name="{{ $row[0] }}" value="0">
                                <input type="checkbox" name="{{ $row[0] }}" value="1" @checked(data_get($org->settings, $row[0], $row[4])) @disabled(! $canManage) onchange="this.form.submit()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    @endforeach
                </form>
                <div class="dup-foot">
                    <div class="dup-stat"><small>Duplicate Rate</small><b>{{ $duplicateStats['rate'] }}%</b> <em class="{{ $duplicateStats['rate_label'] === 'Low' ? 'low' : ($duplicateStats['rate_label'] === 'Medium' ? 'mid' : 'high') }}">{{ $duplicateStats['rate_label'] }}</em></div>
                    <div class="dup-stat"><small>Records Reviewed</small><b>{{ number_format($duplicateStats['reviewed']) }}</b> <span class="text-muted small">This Month</span></div>
                    <div class="dup-stat"><small>Duplicates Merged</small><b>{{ number_format($duplicateStats['merged']) }}</b> <span class="text-muted small">This Month</span></div>
                </div>
            </div>

        @elseif($tab === 'permissions')
            <div class="card-crm settings-card mb-3">
                <div class="card-head"><h5>Permissions Overview</h5></div>
                <div class="perm-wrap">
                    <table class="perm-table">
                        <thead>
                            <tr>
                                <th>Module</th>
                                @foreach($overviewRoles as $role)<th>{{ $role->name }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permissionModules as $label => $slug)
                                <tr>
                                    <td>{{ $label }}</td>
                                    @foreach($overviewRoles as $role)
                                        <td>
                                            @if($role->permissions->contains('slug', $slug))
                                                <i class="bi bi-check-lg yes"></i>
                                            @else
                                                <span class="no">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="perm-foot"><span>Total Roles: {{ $roles->count() }}</span></div>
            </div>
            @foreach($roles as $role)
                <div class="card-crm settings-form-card mb-2">
                    <h6>{{ $role->name }}</h6>
                    <form method="POST" action="{{ route('crm.settings.roles', $role) }}">@csrf @method('PUT')
                        <div class="row">
                            @foreach($permissions as $perm)
                                <div class="col-md-3 form-check"><input type="checkbox" name="permissions[]" value="{{ $perm->id }}" class="form-check-input" @checked($role->permissions->contains($perm->id)) @disabled(! $canManage)> {{ $perm->slug }}</div>
                            @endforeach
                        </div>
                        @if($canManage)<button class="btn btn-sm btn-primary mt-2">Save</button>@endif
                    </form>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.routing-card').forEach(function (card) {
    card.addEventListener('click', function () {
        const input = card.querySelector('input[type="radio"]');
        if (!input || input.disabled) return;
        document.querySelectorAll('.routing-card').forEach(function (el) { el.classList.remove('active'); });
        card.classList.add('active');
        input.checked = true;
    });
});
</script>
@endpush
