@php
    $isOn = function (string $route) {
        return request()->routeIs($route) || request()->routeIs($route.'.*');
    };
@endphp
<aside class="side crm-sidebar">
    <div class="brand">
        <div class="logo"><i class="bi bi-broadcast"></i></div>
        <div>
            <b>GrowLead</b>
            <small>Platform console</small>
        </div>
    </div>
    <div class="ws">
        <div class="mark">S</div>
        <div>
            <b>Super Admin</b>
            <small>Monitor every workspace</small>
        </div>
    </div>
    <nav class="nav">
        <div class="lbl">Platform</div>
        <a href="{{ route('super.dashboard') }}" class="{{ $isOn('super.dashboard') ? 'on' : '' }}">
            <i class="bi bi-grid-1x2"></i> Overview
        </a>
        <a href="{{ route('super.organizations.index') }}" class="{{ $isOn('super.organizations.index') || $isOn('super.organizations.show') ? 'on' : '' }}">
            <i class="bi bi-building"></i> Organizations
        </a>
        <a href="{{ route('super.organizations.create') }}" class="{{ $isOn('super.organizations.create') ? 'on' : '' }}">
            <i class="bi bi-plus-square"></i> New organization
        </a>
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
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dropdown-item">Logout</button>
                </form>
            </li>
        </ul>
    </div>
</aside>
