<div class="row g-3 mb-3">
    @foreach($data['kpis'] as $kpi)
        <div class="col-xl-2 col-md-4">@include('components.kpi-card', ['label'=>$kpi['label'],'value'=>$kpi['value'],'icon'=>$kpi['icon'] ?? 'bi-dot'])</div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-5">
        <div class="card-crm p-3 h-100">
            <h5 class="mb-3">Revenue by agent</h5>
            <div class="chart-box" style="height:320px"><canvas id="agentChart"></canvas></div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card-crm p-3">
            <h5 class="mb-3">Agent leaderboard</h5>
            <table class="table-crm">
                <thead><tr><th></th><th>Agent</th><th>Assigned</th><th>Won</th><th>Revenue</th><th>Conv.</th><th>Avg. response</th></tr></thead>
                <tbody>
                @forelse($data['users'] as $i => $row)
                    <tr>
                        <td><span class="rank {{ ['gold','silver','bronze'][$i] ?? 'silver' }}">{{ $i+1 }}</span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar">{{ $row['user']->initials() }}</div>
                                <div>
                                    <div class="fw-bold">{{ $row['user']->name }}</div>
                                    <div class="small text-muted">{{ $row['user']->roleName() }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $row['assigned'] }}</td>
                        <td>{{ $row['closed'] }}</td>
                        <td>{{ auth()->user()->organization->formatMoney($row['revenue']) }}</td>
                        <td class="fw-bold">{{ $row['conversion'] }}%</td>
                        <td>{{ $row['response'] }}m</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">No agent activity in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
