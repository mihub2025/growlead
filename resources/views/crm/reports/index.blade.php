@extends('layouts.crm')
@section('title', 'Reports & Insights')
@section('content')
<div class="crm-topbar">
    <div>
        <p class="kicker">{{ now()->format('l · j F Y') }}</p>
        <h1 class="crm-title d-inline">Reports & Insights</h1>
        <p class="crm-subtitle">Track performance, uncover trends, and make data-driven decisions.</p>
    </div>
    <div class="crm-actions">
        <a href="{{ route('crm.reports.export', request()->query()) }}" class="btn btn-outline-soft"><i class="bi bi-upload"></i> Export</a>
        <a href="{{ route('crm.reports.index', request()->query()) }}" class="btn btn-outline-soft"><i class="bi bi-calendar-event"></i> Schedule Report</a>
        <a href="{{ route('crm.reports.index', ['tab' => 'custom']) }}" class="btn btn-yellow">+ New Report</a>
    </div>
</div>
<div class="crm-content">
    <form class="filters-bar mb-3 d-flex flex-wrap gap-2 align-items-center" method="GET">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="date-range">
            <i class="bi bi-calendar3"></i>
            <input type="date" name="from" value="{{ request('from', now()->subDays(7)->toDateString()) }}" class="form-control" onchange="this.form.submit()">
            <span class="sep">–</span>
            <input type="date" name="to" value="{{ request('to', now()->toDateString()) }}" class="form-control" onchange="this.form.submit()">
        </div>
        <select name="source_id" class="form-select" style="width:auto" onchange="this.form.submit()"><option value="">All Sources</option>@foreach($sources as $s)<option value="{{ $s->id }}" @selected(request('source_id')==$s->id)>{{ $s->name }}</option>@endforeach</select>
        <select name="team_id" class="form-select" style="width:auto" onchange="this.form.submit()"><option value="">All Teams</option>@foreach($teams as $t)<option value="{{ $t->id }}" @selected(request('team_id')==$t->id)>{{ $t->name }}</option>@endforeach</select>
        <select name="campaign_id" class="form-select" style="width:auto" onchange="this.form.submit()"><option value="">All Campaigns</option>@foreach($campaigns as $c)<option value="{{ $c->id }}" @selected(request('campaign_id')==$c->id)>{{ $c->name }}</option>@endforeach</select>
        <select name="interested_in" class="form-select" style="width:auto" onchange="this.form.submit()"><option value="">All Types</option>@foreach($types as $type)<option value="{{ $type }}" @selected(request('interested_in')==$type)>{{ $type }}</option>@endforeach</select>
        <select name="user_id" class="form-select" style="width:auto" onchange="this.form.submit()"><option value="">All Agents</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(request('user_id')==$u->id)>{{ $u->name }}</option>@endforeach</select>
        <button class="btn btn-outline-soft" type="submit">Apply Filters @if($filterCount)<span class="badge-soft badge-done">{{ $filterCount }}</span>@endif</button>
        @if($filterCount || request()->has('from'))
            <a href="{{ route('crm.reports.index', ['tab' => $tab]) }}" class="small">Clear</a>
        @endif
    </form>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="tabs-bar">
            @foreach(['executive'=>'Executive Summary','sales'=>'Sales Performance','marketing'=>'Marketing Performance','users'=>'Agent Performance','pipeline'=>'Pipeline Health','custom'=>'Custom Report Builder'] as $key=>$label)
                <a class="{{ $tab===$key?'active':'' }}" href="{{ request()->fullUrlWithQuery(['tab'=>$key]) }}">{{ $label }}</a>
            @endforeach
        </div>
        <a href="{{ route('crm.reports.index', ['tab' => 'custom'] + request()->except('tab')) }}" class="small"><i class="bi bi-save"></i> Save as Template</a>
    </div>

    @include('crm.reports.tabs.'.$tab)
</div>
@endsection
@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
function drawChart(id, config) {
    const el = document.getElementById(id);
    if (!el) return;
    new Chart(el, config);
}
const ink = '#161513', green = '#16a34a', gold = '#9a7b3c', muted = '#3d3a35';
drawChart('roiChart', {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(($data['sourceRoi'] ?? collect())->map(fn($r) => $r->source?->name ?? 'Other')) !!},
        datasets: [{ data: {!! json_encode(($data['sourceRoi'] ?? collect())->pluck('leads')) !!}, backgroundColor: [ink, green, gold, muted, '#c4a574'], borderWidth: 0 }]
    },
    options: { cutout: '72%', plugins: { legend: { display: false } }, maintainAspectRatio: false }
});
drawChart('spendChart', {
    type: 'bar',
    data: {
        labels: {!! json_encode(($data['campaigns'] ?? collect())->pluck('name')) !!},
        datasets: [
            { type: 'bar', label: 'Leads', data: {!! json_encode(($data['campaigns'] ?? collect())->pluck('total_leads')) !!}, backgroundColor: ink, borderRadius: 6 },
            { type: 'line', label: 'Qualified', data: {!! json_encode(($data['campaigns'] ?? collect())->pluck('qualified_leads')) !!}, borderColor: green, tension: .4 }
        ]
    },
    options: { plugins: { legend: { position: 'bottom' } }, maintainAspectRatio: false, scales: { x: { ticks: { display: false }, grid: { display: false } } } }
});
drawChart('respChart', {
    type: 'line',
    data: { labels: {!! json_encode($data['responseTrend']['labels'] ?? []) !!}, datasets: [{ data: {!! json_encode($data['responseTrend']['values'] ?? []) !!}, borderColor: gold, backgroundColor: 'rgba(154,123,60,.12)', fill: true, tension: .4, pointRadius: 3 }] },
    options: { plugins: { legend: { display: false } }, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});
drawChart('forecastChart', {
    type: 'line',
    data: { labels: ['W1','W2','W3','W4'], datasets: [
        { label: 'Revenue', data: [{{ (int)(($data['forecast']['revenue'] ?? 0)*0.4) }},{{ (int)(($data['forecast']['revenue'] ?? 0)*0.6) }},{{ (int)(($data['forecast']['revenue'] ?? 0)*0.8) }},{{ (int)($data['forecast']['revenue'] ?? 0) }}], borderColor: ink, tension: .4 },
        { label: 'Deals', data: [{{ max(1,(int)(($data['forecast']['deals'] ?? 0)*0.4)) }},{{ max(1,(int)(($data['forecast']['deals'] ?? 0)*0.6)) }},{{ max(1,(int)(($data['forecast']['deals'] ?? 0)*0.8)) }},{{ (int)($data['forecast']['deals'] ?? 0) }}], borderColor: green, tension: .4 }
    ]},
    options: { plugins: { legend: { position: 'bottom' } }, maintainAspectRatio: false }
});
drawChart('leadTrendChart', {
    type: 'line',
    data: { labels: {!! json_encode($data['leadTrend']['labels'] ?? []) !!}, datasets: [{ label: 'Leads', data: {!! json_encode($data['leadTrend']['values'] ?? []) !!}, borderColor: ink, backgroundColor: 'rgba(22,21,19,.08)', fill: true, tension: .35 }] },
    options: { plugins: { legend: { display: false } }, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});
drawChart('salesMixChart', {
    type: 'doughnut',
    data: {
        labels: ['Won','Open','Lost'],
        datasets: [{ data: [{{ (int)($data['salesMix']['won'] ?? 0) }}, {{ (int)($data['salesMix']['open'] ?? 0) }}, {{ (int)($data['salesMix']['lost'] ?? 0) }}], backgroundColor: [green, ink, '#c2412d'], borderWidth: 0 }]
    },
    options: { cutout: '68%', plugins: { legend: { position: 'bottom' } }, maintainAspectRatio: false }
});
drawChart('revenueChart', {
    type: 'bar',
    data: {
        labels: {!! json_encode($data['revenueTrend']['labels'] ?? []) !!},
        datasets: [
            { type: 'bar', label: 'Revenue', data: {!! json_encode($data['revenueTrend']['values'] ?? []) !!}, backgroundColor: ink, borderRadius: 6 },
            { type: 'line', label: 'Deals', data: {!! json_encode($data['dealsTrend']['values'] ?? []) !!}, borderColor: green, tension: .4, yAxisID: 'y1' }
        ]
    },
    options: { plugins: { legend: { position: 'bottom' } }, maintainAspectRatio: false, scales: { y: { beginAtZero: true }, y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } } } }
});
drawChart('agentChart', {
    type: 'bar',
    data: {
        labels: {!! json_encode(($data['users'] ?? collect())->take(8)->map(fn($r) => $r['user']->name)) !!},
        datasets: [{ label: 'Revenue', data: {!! json_encode(($data['users'] ?? collect())->take(8)->pluck('revenue')) !!}, backgroundColor: ink, borderRadius: 6 }]
    },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, maintainAspectRatio: false, scales: { x: { beginAtZero: true } } }
});
const mapEl = document.getElementById('reportMap');
if (mapEl) {
    const rmap = L.map('reportMap', { zoomControl: true, attributionControl: false }).setView([31.52, 74.35], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(rmap);
    @foreach(($data['locations'] ?? collect()) as $i => $loc)
    L.circle([{{ 31.52 + ($i*0.02) }}, {{ 74.35 + ($i*0.015) }}], { radius: {{ max(400, $loc->total*60) }}, color: ink, fillOpacity:.25 }).bindTooltip(@json(($loc->city ?? 'Unknown').' · '.$loc->total)).addTo(rmap);
    @endforeach
    setTimeout(() => rmap.invalidateSize(), 200);
}
</script>
@endpush
