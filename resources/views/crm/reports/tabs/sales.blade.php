<div class="row g-3 mb-3">
    @foreach($data['kpis'] as $kpi)
        <div class="col-xl-2 col-md-4">@include('components.kpi-card', ['label'=>$kpi['label'],'value'=>$kpi['value'],'icon'=>$kpi['icon'] ?? 'bi-dot'])</div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card-crm p-3">
            <h5 class="mb-3">Revenue &amp; won deals</h5>
            <div class="chart-box" style="height:280px"><canvas id="revenueChart"></canvas></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card-crm p-3 h-100">
            <h5 class="mb-3">Deal mix</h5>
            <div class="chart-box"><canvas id="salesMixChart"></canvas></div>
            <div class="row g-2 mt-2">
                <div class="col-4"><div class="mini-stat"><strong>{{ $data['salesMix']['won'] }}</strong><small>Won · {{ auth()->user()->organization->formatMoney($data['salesMix']['won_value']) }}</small></div></div>
                <div class="col-4"><div class="mini-stat"><strong>{{ $data['salesMix']['open'] }}</strong><small>Open · {{ auth()->user()->organization->formatMoney($data['salesMix']['open_value']) }}</small></div></div>
                <div class="col-4"><div class="mini-stat"><strong>{{ $data['salesMix']['lost'] }}</strong><small>Lost · {{ auth()->user()->organization->formatMoney($data['salesMix']['lost_value']) }}</small></div></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card-crm p-3">
            <h5 class="mb-3">Recent deals</h5>
            <table class="table-crm">
                <thead><tr><th>Deal</th><th>Lead</th><th>Owner</th><th>Status</th><th>Value</th></tr></thead>
                <tbody>
                @forelse($data['deals'] as $deal)
                    <tr>
                        <td class="fw-bold">{{ $deal->name }}</td>
                        <td>{{ $deal->lead->full_name ?? '—' }}</td>
                        <td>{{ $deal->assignedUser->name ?? 'Unassigned' }}</td>
                        <td><span class="badge-soft {{ $deal->status==='won'?'badge-active':($deal->status==='lost'?'badge-hot':'badge-warm') }}">{{ ucfirst($deal->status) }}</span></td>
                        <td>{{ auth()->user()->organization->formatMoney($deal->estimated_value) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">No deals in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card-crm p-3">
            <h5 class="mb-3">Top closers</h5>
            <table class="table-crm lb-table">
                <thead><tr><th></th><th>Agent</th><th>Revenue</th><th>Won</th><th>Conv.</th></tr></thead>
                <tbody>
                @forelse($data['users']->take(6) as $i => $row)
                    <tr>
                        <td><span class="rank {{ ['gold','silver','bronze'][$i] ?? 'silver' }}">{{ $i+1 }}</span></td>
                        <td>{{ $row['user']->name }}</td>
                        <td>{{ auth()->user()->organization->formatMoney($row['revenue']) }}</td>
                        <td>{{ $row['closed'] }}</td>
                        <td>{{ $row['conversion'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">No closers yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
