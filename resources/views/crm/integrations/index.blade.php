@extends('layouts.crm')
@section('title', 'Integrations')
@section('content')
@php
    $canManage = auth()->user()->hasPermission('integrations.manage');
    $statusMeta = [
        'connected' => ['ok', 'Connected'],
        'disconnected' => ['warn', 'Disconnected'],
        'error' => ['bad', 'Error'],
        'syncing' => ['warn', 'Syncing'],
    ];
    $recentLogs = $logs->take(5);
@endphp
<div class="int-page">
    <div class="crm-topbar">
        <div>
            <p class="kicker">Workspace</p>
            <h1 class="crm-title d-inline">Integrations</h1>
            <p class="crm-subtitle">Connect ad platforms, forms, messaging and webhooks.</p>
        </div>
        <div class="crm-actions">
            <a href="{{ route('crm.settings.index', ['tab' => 'integrations']) }}" class="int-help"><span>Need Help?</span> <i class="bi bi-question-circle"></i></a>
        </div>
    </div>

    <div class="crm-content">
        <div class="int-grid-page">
            @foreach($providers as $key => $name)
                @php
                    $item = $integrations[$key] ?? null;
                    $status = $item->status ?? 'disconnected';
                    $meta = $statusMeta[$status] ?? $statusMeta['disconnected'];
                @endphp
                <div class="card-crm int-card">
                    <div class="int-card-head">
                        <div class="int-card-brand">
                            @include('components.source-icon', ['slug' => $key])
                            <strong>{{ $name }}</strong>
                        </div>
                        <span class="int-card-status is-{{ $status }}"><span class="dot {{ $meta[0] }}"></span> {{ $meta[1] }}</span>
                    </div>
                    @if($key === 'meta')
                        <div class="int-card-form">
                            @if($status === 'connected')
                                <div class="int-field">
                                    <i class="bi bi-person-check"></i>
                                    <input class="form-control" value="Connected as {{ data_get($item->settings, 'account_name', 'Meta account') }}" readonly>
                                </div>
                                <p class="int-oauth-note">
                                    Facebook campaigns import from the businesses you select, then every 15 minutes.
                                    @php $picked = data_get($item->settings, 'selected_businesses', []); @endphp
                                    @if($picked)
                                        Syncing {{ collect($picked)->pluck('name')->filter()->join(', ') }}.
                                    @else
                                        Select businesses to import.
                                    @endif
                                    @if($item->last_synced_at)
                                        Last synced {{ $item->last_synced_at->diffForHumans() }}
                                        · {{ (int) data_get($item->settings, 'sync.campaigns_imported', 0) }} campaigns
                                        · {{ (int) data_get($item->settings, 'sync.leads_imported', 0) }} new leads.
                                    @endif
                                </p>
                            @else
                                <p class="int-oauth-note">Sign in with Meta to connect ad accounts and lead forms. A Meta login tab will open so you can authorize GrowLead.</p>
                                @unless($metaConfigured)
                                    <p class="int-card-error">Add META_APP_ID and META_APP_SECRET in .env, then paste this redirect URI in your Meta app:</p>
                                    <div class="int-redirect" data-copy="{{ $metaRedirectUri }}">
                                        <code>{{ $metaRedirectUri }}</code>
                                        <button type="button" class="int-copy-light" title="Copy redirect URI"><i class="bi bi-clipboard"></i></button>
                                    </div>
                                @endunless
                            @endif
                            @if($item && $item->last_error)
                                <div class="int-card-error">{{ $item->last_error }}</div>
                            @endif
                            @if($canManage)
                                <div class="int-card-actions">
                                    @if($status === 'connected')
                                        <a class="btn btn-outline-soft int-save" href="{{ route('crm.integrations.meta.businesses') }}">Businesses</a>
                                        <a class="btn btn-outline-soft int-save" href="{{ route('crm.integrations.meta.capi') }}">Conversions API</a>
                                        <form method="POST" action="{{ route('crm.integrations.meta.sync') }}">
                                            @csrf
                                            <button class="btn btn-primary int-save" type="submit">Sync now</button>
                                        </form>
                                        <form method="POST" action="{{ route('crm.integrations.meta.disconnect') }}">
                                            @csrf
                                            <button class="btn btn-outline-soft int-save" type="submit" data-confirm="Disconnect Meta Ads?">Disconnect</button>
                                        </form>
                                        <a class="btn btn-outline-soft int-save" href="{{ route('crm.integrations.meta.connect') }}" target="_blank" rel="opener" data-oauth-popup>Reconnect</a>
                                    @else
                                        <a class="btn btn-primary int-save" href="{{ route('crm.integrations.meta.connect') }}" target="_blank" rel="opener" data-oauth-popup>Connect</a>
                                    @endif
                                    @if($status !== 'connected' && data_get($item?->settings, 'capi.dataset_id'))
                                        <a class="btn btn-outline-soft int-save" href="{{ route('crm.integrations.meta.capi') }}">Conversions API</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @else
                    <form method="POST" action="{{ route('crm.integrations.store') }}" class="int-card-form">
                        @csrf
                        <input type="hidden" name="provider" value="{{ $key }}">
                        <input type="hidden" name="name" value="{{ $name }}">
                        <div class="int-field">
                            <i class="bi bi-key"></i>
                            <input name="credentials[token]" class="form-control" placeholder="API token / key" autocomplete="off" @disabled(! $canManage)>
                        </div>
                        <div class="int-field">
                            <i class="bi bi-circle"></i>
                            <select name="status" class="form-select" @disabled(! $canManage)>
                                <option value="disconnected" @selected($status === 'disconnected')>Disconnected</option>
                                <option value="connected" @selected($status === 'connected')>Connected</option>
                                <option value="error" @selected($status === 'error')>Error</option>
                                <option value="syncing" @selected($status === 'syncing')>Syncing</option>
                            </select>
                        </div>
                        @if($item && $item->last_error && $status === 'error')
                            <div class="int-card-error">{{ $item->last_error }}</div>
                        @endif
                        @if($canManage)
                            <div class="int-card-actions">
                                <button class="btn btn-primary int-save" type="submit">Save</button>
                            </div>
                        @endif
                    </form>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="card-crm int-bottom">
            <div class="int-webhook">
                <div class="int-webhook-title">
                    <h5>Webhook / API</h5>
                    <i class="bi bi-link-45deg"></i>
                </div>
                <p class="int-webhook-sub">Send lead data to your system in real-time.</p>

                <div class="int-label">Endpoint</div>
                <div class="int-code" data-copy="POST /api/v1/leads/webhook/{source}">
                    <code>POST /api/v1/leads/webhook/{source}</code>
                    <button type="button" class="int-copy" title="Copy endpoint" aria-label="Copy endpoint"><i class="bi bi-clipboard"></i></button>
                </div>

                <div class="int-label">Headers</div>
                <div class="int-header-tags">
                    <span>X-Webhook-Token</span>
                    <span>X-Organization</span>
                </div>

                <div class="int-note">
                    <i class="bi bi-info-circle"></i>
                    <span>Replace {source} with the integration slug (e.g., meta-ads, instagram, website-forms).</span>
                </div>
            </div>

            <div class="int-logs" id="webhook-logs">
                <div class="int-logs-head">
                    <h5>Recent webhook logs</h5>
                    <a href="#webhook-logs-all">View all logs <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="table-wrap">
                    <table class="table-crm int-log-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Response</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($recentLogs as $log)
                            @include('crm.integrations._log-row', ['log' => $log, 'providers' => $providers])
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted py-4 text-center">No webhook events yet. Incoming leads will appear here.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logModalLabel">Webhook event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="smart-list mb-3">
                    <dt>Time</dt><dd id="logTime">—</dd>
                    <dt>Source</dt><dd id="logSource">—</dd>
                    <dt>Status</dt><dd id="logStatus">—</dd>
                    <dt>Response</dt><dd id="logResponse">—</dd>
                    <dt>Result</dt><dd id="logResult">—</dd>
                    <dt>Error</dt><dd id="logError">—</dd>
                </dl>
                <div class="int-label">Payload</div>
                <pre class="int-payload" id="logPayload">{}</pre>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="webhook-logs-all" tabindex="-1" aria-labelledby="allLogsLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="allLogsLabel">Webhook logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-wrap">
                    <table class="table-crm int-log-table mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Response</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($logs as $log)
                            @include('crm.integrations._log-row', ['log' => $log, 'providers' => $providers])
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted py-4 text-center">No webhook events yet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (new URLSearchParams(window.location.search).get('oauth') === '1' && window.opener && !window.opener.closed) {
        try { window.opener.location.reload(); } catch (e) {}
        window.close();
    }

    document.querySelectorAll('[data-oauth-popup]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            const popup = window.open(el.href, 'meta-oauth');
            if (!popup) {
                window.location.href = el.href;
            }
        });
    });

    document.querySelectorAll('.int-copy, .int-copy-light').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const box = btn.closest('[data-copy]');
            const text = box ? box.getAttribute('data-copy') : '';
            if (!text) return;
            navigator.clipboard.writeText(text).then(function () {
                btn.innerHTML = '<i class="bi bi-check2"></i>';
                setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1600);
            });
        });
    });

    const allLogsLink = document.querySelector('a[href="#webhook-logs-all"]');
    const allLogsModal = document.getElementById('webhook-logs-all');
    if (allLogsLink && allLogsModal && window.bootstrap) {
        allLogsLink.addEventListener('click', function (e) {
            e.preventDefault();
            bootstrap.Modal.getOrCreateInstance(allLogsModal).show();
        });
    }

    const logModal = document.getElementById('logModal');
    if (logModal) {
        logModal.addEventListener('show.bs.modal', function (event) {
            if (allLogsModal && allLogsModal.classList.contains('show') && window.bootstrap) {
                const inst = bootstrap.Modal.getInstance(allLogsModal);
                if (inst) inst.hide();
            }
            const row = event.relatedTarget;
            if (!row || !row.getAttribute) return;
            const set = function (id, value) {
                const el = document.getElementById(id);
                if (el) el.textContent = value || '—';
            };
            set('logTime', row.getAttribute('data-time'));
            set('logSource', row.getAttribute('data-source'));
            set('logStatus', row.getAttribute('data-status'));
            set('logResponse', row.getAttribute('data-response'));
            set('logResult', row.getAttribute('data-result'));
            set('logError', row.getAttribute('data-error'));
            const payloadEl = document.getElementById('logPayload');
            if (payloadEl) {
                try {
                    payloadEl.textContent = JSON.stringify(JSON.parse(row.getAttribute('data-payload') || '{}'), null, 2);
                } catch (e) {
                    payloadEl.textContent = row.getAttribute('data-payload') || '{}';
                }
            }
        });
    }
});
</script>
@endpush
