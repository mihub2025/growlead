<header class="top">
    <button class="ghost mobile-toggle" type="button" data-sidebar-toggle aria-label="Open menu" style="width:36px;padding:0;display:none">
        <i class="bi bi-list"></i>
    </button>
    <div class="search" style="pointer-events:none">
        <i class="bi bi-shield-check"></i>
        <input value="Platform administration" readonly aria-label="Platform administration" tabindex="-1">
    </div>
    <div class="top-right">
        @stack('chrome')
        <a href="{{ route('super.organizations.create') }}" class="primary">New organization</a>
        <span class="avatar" style="display:grid;place-items:center;background:var(--ink);color:#f6f1e8;font-size:11px;font-weight:700">{{ auth()->user()->initials() }}</span>
    </div>
</header>
