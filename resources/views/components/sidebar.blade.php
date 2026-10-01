@php
    $org = auth()->user()->organization;
    $orgName = $org->name ?? 'Workspace';
    $orgMark = strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $orgName) ?: 'W', 0, 1));
    $liveCampaigns = $org ? $org->campaigns()->where('status', 'active')->count() : 0;
    $workspace = [
        ['route' => 'crm.dashboard', 'icon' => 'bi-grid-1x2', 'label' => 'Dashboard', 'perm' => 'dashboard.view'],
        ['route' => 'crm.campaigns.index', 'icon' => 'bi-megaphone', 'label' => 'Campaigns', 'perm' => 'campaigns.view'],
        ['route' => 'crm.leads.index', 'icon' => 'bi-people', 'label' => 'Leads', 'perm' => 'leads.view'],
        ['route' => 'crm.ai.index', 'icon' => 'bi-stars', 'label' => 'AI Copilot', 'perm' => 'ai.view'],
    ];
    $manage = [
        ['route' => 'crm.reports.index', 'icon' => 'bi-bar-chart', 'label' => 'Reports', 'perm' => 'reports.view'],
        ['route' => 'crm.users.index', 'icon' => 'bi-person-badge', 'label' => 'Users', 'perm' => 'users.view'],
        ['route' => 'crm.automations.index', 'icon' => 'bi-lightning', 'label' => 'Automations', 'perm' => 'automations.view'],
        ['route' => 'crm.integrations.index', 'icon' => 'bi-plug', 'label' => 'Integrations', 'perm' => 'integrations.view'],
        ['route' => 'crm.settings.index', 'icon' => 'bi-gear', 'label' => 'Settings', 'perm' => 'settings.view'],
    ];
    $isOn = function (string $route) {
        return request()->routeIs(str_replace('.index', '', $route).'*') || request()->routeIs($route);
    };
@endphp
<aside class="side crm-sidebar">
    <div class="brand">
        <div class="logo"><i class="bi bi-broadcast"></i></div>
        <div>
            <b>GrowLead</b>
            <small>Workspace CRM</small>
        </div>
    </div>
    <div class="ws">
        <div class="mark">{{ $orgMark }}</div>
        <div>
            <b>{{ $orgName }}</b>
            <small><span class="live"></span>{{ $liveCampaigns }} campaign{{ $liveCampaigns === 1 ? '' : 's' }} live</small>
        </div>
    </div>
    <nav class="nav">
        <div class="lbl">Workspace</div>
        @foreach($workspace as $item)
            @can($item['perm'])
                <a href="{{ route($item['route']) }}" class="{{ $isOn($item['route']) ? 'on' : '' }}">
                    <i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}
                </a>
            @endcan
        @endforeach
        <div class="lbl">Manage</div>
        @foreach($manage as $item)
            @can($item['perm'])
                <a href="{{ route($item['route']) }}" class="{{ $isOn($item['route']) ? 'on' : '' }}">
                    <i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}
                </a>
            @endcan
        @endforeach
    </nav>
    <div class="dropdown" style="margin-top:auto">
        <a class="side-user" href="#" data-bs-toggle="dropdown">
            <div class="avatar" style="display:grid;place-items:center;background:var(--ink);color:#f6f1e8;font-size:11px;font-weight:700">{{ auth()->user()->initials() }}</div>
            <div>
                <b>{{ auth()->user()->name }}</b>
                <small>{{ auth()->user()->roleName() }}</small>
            </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('crm.settings.profile') }}">Profile</a></li>
            <li><a class="dropdown-item" href="{{ route('crm.settings.index') }}">Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dropdown-item">Logout</button>
                </form>
            </li>
        </ul>
    </div>
</aside>
