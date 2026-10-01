<div class="row g-3 mb-3">
    @foreach($data['kpis'] as $kpi)
        <div class="col-xl-2 col-md-4">@include('components.kpi-card', ['label'=>$kpi['label'],'value'=>$kpi['value'],'icon'=>$kpi['icon'] ?? 'bi-dot'])</div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-6 d-flex">
        <div class="card-crm p-3 w-100 h-100">
            <h5 class="mb-3">Sales Funnel</h5>
            @php $countPipe = max(1, $data['funnel']->count()); $firstCount = max(1, optional($data['funnel']->first())['count'] ?? 1); @endphp
            <div class="funnel">
                @foreach($data['funnel'] as $i => $row)
                    @php $w = 94 - ($i * (46 / max(1, $countPipe - 1))); @endphp
                    <div class="funnel-row">
                        <div class="funnel-bar" style="width: {{ max(36, $w) }}%; background: {{ $row['color'] ?? '#2563eb' }}">{{ number_format($row['count']) }}</div>
                        <div class="funnel-meta">{{ $row['name'] }}<small>{{ $firstCount ? round(($row['count']/$firstCount)*100,1) : 0 }}%</small></div>
                    </div>
                @endforeach
            </div>
            @php $last = $data['funnel']->last(); $conv = $firstCount ? round((($last['count'] ?? 0)/$firstCount)*100,1) : 0; @endphp
            <div class="funnel-foot">Overall Conversion Rate <strong class="text-success">{{ $conv }}%</strong></div>
        </div>
    </div>
    <div class="col-xl-6 d-flex">
        <div class="card-crm p-3 w-100 h-100 d-flex flex-column">
            <h5 class="mb-3">Source ROI</h5>
            <div class="chart-box flex-grow-1" style="height:auto;min-height:220px">
                <canvas id="roiChart"></canvas>
                <div class="chart-center">{{ $data['kpis'][0]['value'] ?? '' }}<small>Total Revenue</small></div>
            </div>
            <div class="src-grid">
                @forelse($data['sourceRoi'] as $row)
                    <div class="src-grid-item">
                        <span class="d-inline-flex align-items-center gap-1 text-truncate">@include('components.source-icon', ['source'=>$row->source]) {{ $row->source->name ?? 'Other' }}</span>
                        <span class="text-muted">{{ $row->leads }} · {{ $row->qualified }}</span>
                    </div>
                @empty
                    <div class="text-muted small">No source data in this period.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card-crm p-3">
            <h5 class="mb-3">Spend vs Qualified Leads</h5>
            <div class="chart-box" style="height:280px"><canvas id="spendChart"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3 report-eq-row">
    <div class="col-xl-4">
        <div class="card-crm p-3 report-eq-card">
            <h5 class="mb-3">Agent Leaderboard</h5>
            <table class="table-crm lb-table">
                <thead><tr><th></th><th>Agent</th><th>Revenue</th><th>Deals</th><th>Conv.</th></tr></thead>
                <tbody>
                @forelse($data['users']->take(5) as $i => $row)
                    <tr>
                        <td><span class="rank {{ ['gold','silver','bronze'][$i] ?? 'silver' }}">{{ $i+1 }}</span></td>
                        <td><div class="d-flex align-items-center gap-2"><div class="avatar">{{ $row['user']->initials() }}</div>{{ $row['user']->name }}</div></td>
                        <td>{{ auth()->user()->organization->formatMoney($row['revenue']) }}</td>
                        <td>{{ $row['closed'] }}</td>
                        <td class="text-success fw-bold">{{ $row['conversion'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">No agent results yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card-crm p-3 report-eq-card">
            <h5 class="mb-3">Response Time Trend</h5>
            <div class="chart-box"><canvas id="respChart"></canvas></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card-crm p-3 report-eq-card">
            <h5 class="mb-3">Area-wise Demand Heatmap</h5>
            <div id="reportMap" class="map-mini"></div>
            <div class="small text-muted mt-2">Low <span class="legend-dot" style="background:#93c5fd"></span> — <span class="legend-dot" style="background:#ef4444"></span> High</div>
        </div>
    </div>
</div>

<div class="row g-3 report-eq-row">
    <div class="col-xl-4">
        <div class="card-crm p-3 report-eq-card">
            <h5 class="mb-2">Deal Forecast</h5>
            <div class="mb-2"><strong>{{ auth()->user()->organization->formatMoney($data['forecast']['revenue']) }}</strong> <span class="text-muted">forecasted revenue</span></div>
            <div class="small text-muted mb-2">{{ $data['forecast']['deals'] }} forecasted deals</div>
            <div class="chart-box"><canvas id="forecastChart"></canvas></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card-crm p-3 report-eq-card">
            <h5 class="mb-3">Pipeline Health</h5>
            @php $ph = max(1, $data['pipelineHealth']['healthy']+$data['pipelineHealth']['at_risk']+$data['pipelineHealth']['stalled']); @endphp
            <div class="health-bar" style="height:10px">
                <span style="width:{{ ($data['pipelineHealth']['healthy']/$ph)*100 }}%;background:#16a34a"></span>
                <span style="width:{{ ($data['pipelineHealth']['at_risk']/$ph)*100 }}%;background:#f59e0b"></span>
                <span style="width:{{ ($data['pipelineHealth']['stalled']/$ph)*100 }}%;background:#ef4444"></span>
            </div>
            <div class="health-legend">
                <span><i style="background:#16a34a"></i> Healthy</span>
                <span><i style="background:#f59e0b"></i> At risk</span>
                <span><i style="background:#ef4444"></i> Stalled</span>
            </div>
            <div class="health-grid">
                <div class="mini-stat"><strong>{{ $data['pipelineHealth']['healthy'] }}</strong><small>Active deals</small></div>
                <div class="mini-stat"><strong>{{ $data['pipelineHealth']['at_risk'] }}</strong><small>Deals at risk</small></div>
                <div class="mini-stat"><strong>{{ $data['pipelineHealth']['stalled'] }}</strong><small>Stalled deals</small></div>
                <div class="mini-stat"><strong>{{ $data['pipelineHealth']['won'] }}</strong><small>Won this period</small></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card-crm p-3 report-eq-card">
            <h5 class="mb-3"><i class="bi bi-stars text-primary"></i> AI Insights <span class="badge-soft badge-score-mid">Beta</span></h5>
            <div class="fw-bold mb-1">Key Takeaways</div>
            <ul class="small mb-3">@foreach($takeaways as $t)<li>{{ $t }}</li>@endforeach</ul>
            <div class="fw-bold mb-1">Recommended Actions</div>
            <ul class="small mb-3 flex-grow-1">@foreach($actions as $t)<li>{{ $t }}</li>@endforeach</ul>
            <a href="{{ route('crm.ai.index') }}" class="mt-auto">View All Insights →</a>
        </div>
    </div>
</div>
