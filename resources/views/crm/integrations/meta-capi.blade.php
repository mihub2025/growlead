@extends('layouts.crm')
@section('title', 'Meta Conversions API')
@section('content')
@php
    $canManage = auth()->user()->hasPermission('integrations.manage');
@endphp
<div class="crm-content">
    <div class="crm-topbar">
        <div>
            <a class="fb-biz-back" href="{{ route('crm.integrations.index') }}"><i class="bi bi-arrow-left"></i> Back to integrations</a>
            <p class="kicker mt-2">Meta Ads</p>
            <h1 class="crm-title">Conversions API for CRM</h1>
            <p class="crm-subtitle">Send qualified lead outcomes from GrowLead back to Meta for Conversion Leads optimization.</p>
        </div>
        <div class="crm-actions">
            <a href="{{ route('crm.integrations.meta.capi.logs') }}" class="btn btn-outline-soft">CAPI event logs</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card-crm p-4" style="max-width:720px">
        <form method="POST" action="{{ route('crm.integrations.meta.capi.update') }}">
            @csrf
            <div class="mb-3 form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="capiEnabled" name="enabled" value="1" @checked(old('enabled', $settings['enabled'])) @disabled(! $canManage)>
                <label class="form-check-label" for="capiEnabled">Enable CAPI</label>
            </div>

            <div class="mb-3">
                <label class="form-label">Dataset ID / Pixel ID</label>
                <input name="dataset_id" class="form-control" value="{{ old('dataset_id', $settings['dataset_id']) }}" placeholder="Meta Dataset / Pixel ID" autocomplete="off" @disabled(! $canManage)>
                <div class="form-text">Use the CRM dataset / Pixel configured for Conversion Leads in Events Manager.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">CAPI Access Token</label>
                <input name="capi_access_token" type="password" class="form-control" value="" placeholder="{{ $settings['has_access_token'] ? '•••••••••••• (leave blank to keep current)' : 'Paste Conversions API access token' }}" autocomplete="new-password" @disabled(! $canManage)>
                <div class="form-text">
                    @if($settings['has_access_token'])
                        A token is saved (encrypted). Leave blank to keep it.
                    @else
                        Generate a token in Meta Events Manager → Settings → Conversions API.
                    @endif
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Lead Event Source</label>
                <input name="lead_event_source" class="form-control" value="{{ old('lead_event_source', $settings['lead_event_source'] ?: 'GrowLead') }}" @disabled(! $canManage)>
                <div class="form-text">Sent as <code>custom_data.lead_event_source</code> (CRM name Meta expects).</div>
            </div>

            <div class="mb-4">
                <label class="form-label">Test Event Code (optional)</label>
                <input name="test_event_code" class="form-control" value="{{ old('test_event_code', $settings['test_event_code']) }}" placeholder="TEST12345" autocomplete="off" @disabled(! $canManage)>
            </div>

            @if($canManage)
                <button type="submit" class="btn btn-primary">Save Settings</button>
            @endif
        </form>

        <hr class="my-4">

        <h5 class="mb-2">Send Test Event</h5>
        <p class="text-muted small">Uses Meta’s <code>test_event_code</code> so the event appears under Test Events in Events Manager. Uses a recent Meta lead from this organization.</p>
        <form method="POST" action="{{ route('crm.integrations.meta.capi.test') }}" class="d-flex flex-wrap gap-2 align-items-end">
            @csrf
            <div style="min-width:220px;flex:1">
                <label class="form-label">Test Event Code</label>
                <input name="test_event_code" class="form-control" value="{{ old('test_event_code', $settings['test_event_code']) }}" placeholder="TEST12345" @disabled(! $canManage)>
            </div>
            @if($canManage)
                <button type="submit" class="btn btn-outline-soft">Send Test Event</button>
            @endif
        </form>

        <div class="mt-3">
            <strong>Connection / Test Status:</strong>
            @if($settings['last_test_at'])
                @if($settings['last_test_ok'])
                    <span class="text-success">✓ Test event received by Meta</span>
                    <span class="text-muted small">({{ \Illuminate\Support\Carbon::parse($settings['last_test_at'])->diffForHumans() }})</span>
                @else
                    <span class="text-danger">✕ Error: {{ $settings['last_error'] ?: 'Test failed' }}</span>
                @endif
            @else
                <span class="text-muted">No test sent yet.</span>
            @endif
        </div>
    </div>
</div>
@endsection
