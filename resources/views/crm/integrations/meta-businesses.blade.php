@extends('layouts.crm')
@section('title', 'Select Facebook businesses')
@php
    $accountName = data_get($integration->settings, 'account_name', 'Meta account');
    $avatarColors = ['#7c3aed', '#2563eb', '#dc2626', '#16a34a', '#ea580c', '#0891b2', '#db2777', '#4f46e5'];
@endphp
@section('content')
<div class="crm-content">
<div class="fb-biz">
    <a class="fb-biz-back" href="{{ route('crm.integrations.index') }}"><i class="bi bi-arrow-left"></i> Back to integrations</a>
    <h1 class="fb-biz-title">Select Facebook businesses</h1>
    <p class="fb-biz-sub">Choose which Business Managers GrowLead should import. Only those campaigns and leads will sync.</p>
    <p class="fb-biz-hint">This list is what Facebook returns for <strong>{{ $accountName }}</strong>. Other Business Managers (Green Aura, RevX, Joeyco) appear only if this same Facebook user is a member of them. Names ending in <em>(Read-Only)</em> come from Facebook — they are shared, partner, or WhatsApp ad accounts this login can view but not fully manage. Duplicate names are different ad account IDs.</p>

    <div class="fb-biz-account">
        <div class="fb-biz-account-id">
            <span class="fb-biz-fb"><i class="bi bi-facebook"></i></span>
            <div>
                <strong>Connected as {{ $accountName }}</strong>
                <small>Facebook account</small>
            </div>
        </div>
        <a class="fb-biz-change" href="{{ route('crm.integrations.meta.connect') }}" target="_blank" rel="opener" data-oauth-popup>
            Change account <i class="bi bi-arrow-repeat"></i>
        </a>
    </div>

    @if(! empty($warnings))
        @php $warnText = implode(' ', array_slice($warnings, 0, 2)); @endphp
        <div class="fb-biz-warn{{ str_contains(strtolower($warnText), 'rate') ? ' is-soft' : '' }}">{{ $warnText }}</div>
    @endif

    <form method="POST" action="{{ route('crm.integrations.meta.businesses.save') }}" id="fb-biz-form">
        @csrf
        <div class="fb-biz-card">
            <div class="fb-biz-card-head">
                <div class="fb-biz-card-label">
                    <i class="bi bi-building"></i>
                    <span>Facebook Business Managers</span>
                    <em>{{ count($businesses) }}</em>
                </div>
                <div class="fb-biz-tools">
                    <label class="fb-biz-search">
                        <i class="bi bi-search"></i>
                        <input type="search" id="fb-biz-q" placeholder="Search business managers..." autocomplete="off">
                    </label>
                    <button type="button" class="fb-biz-filter" id="fb-biz-filter" title="Hide businesses with 0 ad accounts" aria-pressed="false">
                        <i class="bi bi-funnel"></i>
                    </button>
                    <a class="fb-biz-reload" href="{{ route('crm.integrations.meta.businesses', ['refresh' => 1]) }}" title="Reload Business Managers from Facebook">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </div>

            <div class="fb-biz-list" id="fb-biz-list">
                @forelse($businesses as $index => $business)
                    @php
                        $id = (string) $business['id'];
                        $name = (string) ($business['name'] ?? $id);
                        $count = count($business['ad_accounts'] ?? []);
                        $letter = strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'B', 0, 1));
                        $color = $avatarColors[$index % count($avatarColors)];
                        $checked = in_array($id, $selected, true) || (count($selected) === 0 && count($businesses) === 1);
                    @endphp
                    <div class="fb-biz-row{{ $checked ? ' is-on' : '' }}" data-name="{{ strtolower($name) }}" data-accounts="{{ $count }}">
                        <label class="fb-biz-row-main">
                            <input type="checkbox" name="business_ids[]" value="{{ $id }}" @checked($checked)>
                            <span class="fb-biz-avatar" style="background:{{ $color }}">{{ $letter }}</span>
                            <span class="fb-biz-copy">
                                <strong>{{ $name }}</strong>
                                <small>{{ $count }} ad account{{ $count === 1 ? '' : 's' }}</small>
                            </span>
                        </label>
                        <button type="button" class="fb-biz-more" aria-expanded="false" @disabled($count === 0) title="View ad accounts">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                        @if($count)
                            <div class="fb-biz-accounts" hidden>
                                @foreach($business['ad_accounts'] as $account)
                                    @php
                                        $adName = (string) ($account['name'] ?? ($account['id'] ?? 'Ad account'));
                                        $readOnly = str_contains($adName, '(Read-Only)');
                                        $accountId = (string) ($account['account_id'] ?? preg_replace('/^act_/', '', (string) ($account['id'] ?? '')));
                                    @endphp
                                    <div>
                                        {{ $adName }}
                                        @if($readOnly)<span class="fb-biz-ro">shared / view-only</span>@endif
                                        @if($accountId)<small>ID {{ $accountId }}</small>@endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="fb-biz-empty">No Business Managers were found for this Facebook login.</div>
                @endforelse
            </div>

            <div class="fb-biz-empty-search" id="fb-biz-empty-search" hidden>No businesses match that search.</div>

            <div class="fb-biz-foot">
                <span id="fb-biz-count">0 selected</span>
                <div class="fb-biz-actions">
                    <a href="{{ route('crm.integrations.index') }}" class="btn fb-biz-cancel">Cancel</a>
                    <button class="btn fb-biz-go" type="submit">Continue</button>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (new URLSearchParams(window.location.search).get('oauth') === '1' && window.opener && !window.opener.closed) {
        try { window.opener.location.href = window.location.pathname; } catch (e) {}
        window.close();
    }

    document.querySelectorAll('[data-oauth-popup]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            const popup = window.open(el.href, 'meta-oauth');
            if (!popup) window.location.href = el.href;
        });
    });

    const list = document.getElementById('fb-biz-list');
    const countEl = document.getElementById('fb-biz-count');
    const search = document.getElementById('fb-biz-q');
    const filterBtn = document.getElementById('fb-biz-filter');
    const emptySearch = document.getElementById('fb-biz-empty-search');
    if (!list) return;

    function selectedCount() {
        return list.querySelectorAll('input[type="checkbox"]:checked').length;
    }

    function refreshCount() {
        const n = selectedCount();
        countEl.textContent = n + ' selected';
    }

    function applyFilters() {
        const q = (search.value || '').trim().toLowerCase();
        const hideEmpty = filterBtn.classList.contains('is-on');
        let visible = 0;
        list.querySelectorAll('.fb-biz-row').forEach(function (row) {
            const name = row.getAttribute('data-name') || '';
            const accounts = parseInt(row.getAttribute('data-accounts') || '0', 10);
            const show = (!q || name.indexOf(q) !== -1) && (!hideEmpty || accounts > 0);
            row.hidden = !show;
            if (show) visible += 1;
        });
        emptySearch.hidden = visible > 0;
    }

    list.addEventListener('change', function (e) {
        const input = e.target.closest('input[type="checkbox"]');
        if (!input) return;
        input.closest('.fb-biz-row').classList.toggle('is-on', input.checked);
        refreshCount();
    });

    list.addEventListener('click', function (e) {
        const more = e.target.closest('.fb-biz-more');
        if (!more || more.disabled) return;
        const row = more.closest('.fb-biz-row');
        const panel = row.querySelector('.fb-biz-accounts');
        const open = more.getAttribute('aria-expanded') === 'true';
        more.setAttribute('aria-expanded', open ? 'false' : 'true');
        row.classList.toggle('is-open', !open);
        if (panel) panel.hidden = open;
    });

    search.addEventListener('input', applyFilters);
    filterBtn.addEventListener('click', function () {
        filterBtn.classList.toggle('is-on');
        filterBtn.setAttribute('aria-pressed', filterBtn.classList.contains('is-on') ? 'true' : 'false');
        applyFilters();
    });

    refreshCount();
});
</script>
@endpush
