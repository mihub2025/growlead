<header class="top">
    <button class="ghost mobile-toggle" type="button" data-sidebar-toggle aria-label="Open menu" style="width:36px;padding:0;display:none">
        <i class="bi bi-list"></i>
    </button>
    <form class="search" method="GET" action="{{ route('crm.search') }}">
        <i class="bi bi-search"></i>
        <input name="q" value="{{ request('q') }}" placeholder="Search leads, campaigns, people" aria-label="Search">
        <span class="kbd">⌘K</span>
    </form>
    <div class="top-right">
        @stack('chrome')
        @can('campaigns.create')
            <a href="{{ route('crm.campaigns.create') }}" class="primary">New campaign</a>
        @endcan
        <a class="icon-btn" href="{{ route('crm.ai.index') }}" aria-label="Notifications">
            <i class="bi bi-bell"></i>
            <span class="ping"></span>
        </a>
        <a href="{{ route('crm.settings.profile') }}" class="avatar" style="display:grid;place-items:center;background:var(--ink);color:#f6f1e8;font-size:11px;font-weight:700;text-decoration:none">{{ auth()->user()->initials() }}</a>
    </div>
</header>
