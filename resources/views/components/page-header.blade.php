<div class="head crm-topbar">
    <div>
        <p class="kicker">{{ $kicker ?? now()->format('l · j F Y') }}</p>
        <h1 class="serif">{{ $title }}</h1>
        @isset($badge)<span class="badge-soft badge-score-mid">{{ $badge }}</span>@endisset
        @isset($subtitle)<p>{{ $subtitle }}</p>@endisset
    </div>
    <div class="crm-actions">
        {{ $actions ?? '' }}
    </div>
</div>
