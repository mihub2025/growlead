<div class="row g-3 mb-3">
    @foreach($data['kpis'] as $kpi)
        <div class="col-xl-2 col-md-4">@include('components.kpi-card', ['label'=>$kpi['label'],'value'=>$kpi['value'],'icon'=>$kpi['icon'] ?? 'bi-dot'])</div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-7">
        <div class="card-crm p-3">
            <h5 class="mb-3">Lead volume</h5>
            <div class="chart-box" style="height:260px"><canvas id="leadTrendChart"></canvas></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card-crm p-3 h-100 d-flex flex-column">
            <h5 class="mb-3">Source performance</h5>
            <div class="chart-box flex-grow-1" style="min-height:180px"><canvas id="roiChart"></canvas></div>
            <div class="src-grid mt-2">
                @forelse($data['sourceRoi'] as $row)
                    <div class="src-grid-item">
                        <span class="d-inline-flex align-items-center gap-1 text-truncate">@include('components.source-icon', ['source'=>$row->source]) {{ $row->source->name ?? 'Other' }}</span>
                        <span class="text-muted">{{ $row->leads }} leads · {{ $row->qualified }} qual.</span>
                    </div>
                @empty
                    <div class="text-muted small">No source data in this period.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card-crm p-3">
    <h5 class="mb-3">Campaign performance</h5>
    <table class="table-crm">
        <thead><tr><th>Campaign</th><th>Source</th><th>Status</th><th>Spend</th><th>Leads</th><th>Qualified</th><th>CPL</th><th>Revenue</th><th>ROI</th></tr></thead>
        <tbody>
        @forelse($data['campaignRows'] as $campaign)
            <tr>
                <td class="fw-bold">{{ $campaign->name }}</td>
                <td>{{ $campaign->source->name ?? '—' }}</td>
                <td><span class="badge-soft {{ $campaign->status==='active'?'badge-active':($campaign->status==='paused'?'badge-paused':'badge-neutral') }}">{{ ucfirst($campaign->status ?: 'draft') }}</span></td>
                <td>{{ auth()->user()->organization->formatMoney($campaign->total_spend) }}</td>
                <td>{{ number_format($campaign->total_leads) }}</td>
                <td>{{ number_format($campaign->qualified_leads) }}</td>
                <td>{{ auth()->user()->organization->formatMoney($campaign->costPerLead()) }}</td>
                <td>{{ auth()->user()->organization->formatMoney($campaign->revenue) }}</td>
                <td>{{ $campaign->roi() }}x</td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-muted">No campaigns yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
