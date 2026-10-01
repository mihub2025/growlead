<div class="row g-3 mb-3">
    @foreach($data['kpis'] as $kpi)
        <div class="col-xl-2 col-md-4">@include('components.kpi-card', ['label'=>$kpi['label'],'value'=>$kpi['value'],'icon'=>$kpi['icon'] ?? 'bi-dot'])</div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="card-crm p-3">
            <h5 class="mb-3">Pipeline funnel</h5>
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
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card-crm p-3">
            <h5 class="mb-3">Health mix</h5>
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
                <div class="mini-stat"><strong>{{ $data['pipelineHealth']['healthy'] }}</strong><small>Healthy</small></div>
                <div class="mini-stat"><strong>{{ $data['pipelineHealth']['at_risk'] }}</strong><small>At risk</small></div>
                <div class="mini-stat"><strong>{{ $data['pipelineHealth']['stalled'] }}</strong><small>Stalled</small></div>
                <div class="mini-stat"><strong>{{ $data['pipelineHealth']['won'] }}</strong><small>Won this period</small></div>
            </div>
            <div class="chart-box mt-3"><canvas id="forecastChart"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card-crm p-3">
            <h5 class="mb-3">Stage breakdown</h5>
            <table class="table-crm">
                <thead><tr><th>Stage</th><th>Leads</th><th>Open deals</th><th>Value</th></tr></thead>
                <tbody>
                @forelse($data['stageRows'] as $row)
                    <tr>
                        <td><span class="sw d-inline-block me-1" style="width:8px;height:8px;border-radius:50%;background:{{ $row['color'] ?: '#161513' }}"></span>{{ $row['name'] }}</td>
                        <td>{{ $row['leads'] }}</td>
                        <td>{{ $row['deals'] }}</td>
                        <td>{{ auth()->user()->organization->formatMoney($row['value']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No pipeline stages.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card-crm p-3">
            <h5 class="mb-3">Stalled deals</h5>
            @forelse($data['stalledDeals'] as $deal)
                <div class="deal">
                    <div class="r">{{ $deal->name }}<span class="num">{{ auth()->user()->organization->formatMoney($deal->estimated_value) }}</span></div>
                    <small>{{ $deal->lead->full_name ?? '' }} · {{ $deal->assignedUser->name ?? 'Unassigned' }} · {{ optional($deal->expected_close_date)?->toFormattedDateString() ?: 'No close date' }}</small>
                </div>
            @empty
                <div class="text-muted small">No stalled deals.</div>
            @endforelse
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card-crm p-3">
            <h5 class="mb-3">At-risk deals</h5>
            @forelse($data['riskDeals'] as $deal)
                <div class="deal">
                    <div class="r">{{ $deal->name }}<span class="num">{{ (int) $deal->probability }}%</span></div>
                    <small>{{ $deal->lead->full_name ?? '' }} · {{ auth()->user()->organization->formatMoney($deal->estimated_value) }}</small>
                    <div class="pbar"><i style="width:{{ (int) $deal->probability }}%"></i></div>
                </div>
            @empty
                <div class="text-muted small">No at-risk deals.</div>
            @endforelse
        </div>
    </div>
</div>
