@extends('layouts.crm')
@section('title', 'Dashboard')
@php
    $first = explode(' ', auth()->user()->name)[0] ?? auth()->user()->name;
    $hour = (int) now()->format('G');
    $hello = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $kpis = collect($data['kpis']);
    $totalKpi = $kpis->firstWhere('key', 'total_leads');
    $showKpis = $kpis->whereIn('key', ['qualified', 'opportunities', 'cpl', 'revenue'])->values();
    if ($showKpis->count() < 4) { $showKpis = $kpis->take(4); }
    $firstStage = $data['pipeline']->first();
    $lastStage = $data['pipeline']->last();
    $conv = ($firstStage && ($firstStage['count'] ?? 0)) ? round((($lastStage['count'] ?? 0) / max(1, $firstStage['count'])) * 100, 1) : 0;
    $sourceMax = max(1, $data['sources']->max('total') ?: 1);
    $sourceTotal = max(1, $data['sources']->sum('total'));
    $from = $filters['from'] ?? now()->subDays(14)->toDateString();
    $to = $filters['to'] ?? now()->toDateString();
    $span = (int) round(abs(\Carbon\Carbon::parse($from)->diffInDays(\Carbon\Carbon::parse($to))));
    $insight = $data['insights'][0] ?? null;
    $insight2 = $data['insights'][2] ?? null;
    $insight3 = $data['insights'][3] ?? null;
    $kpiIcons = [
        'qualified' => 'bi-check2-circle',
        'opportunities' => 'bi-briefcase',
        'cpl' => 'bi-coin',
        'revenue' => 'bi-graph-up-arrow',
    ];
@endphp
@push('chrome')
    <a href="{{ route('crm.leads.export') }}" class="ghost">Export</a>
@endpush
@section('content')
<main class="page">
    <div class="head">
        <div>
            <p class="kicker">{{ now()->format('l · j F Y') }}</p>
            <h1 class="serif">{{ $hello }}, {{ $first }}</h1>
            <p>{{ number_format($totalKpi['value'] ?? 0) }} leads this period · {{ $data['tasks']['unresponded'] }} waiting past SLA</p>
        </div>
        <div class="seg">
            @foreach([7 => '7d', 14 => '14d', 30 => '30d'] as $days => $label)
                <a class="{{ $span === $days ? 'on' : '' }}" href="{{ request()->fullUrlWithQuery(['from' => now()->subDays($days)->toDateString(), 'to' => now()->toDateString()]) }}">{{ $label }}</a>
            @endforeach
            <a class="{{ request('from') === now()->startOfQuarter()->toDateString() ? 'on' : '' }}" href="{{ request()->fullUrlWithQuery(['from' => now()->startOfQuarter()->toDateString(), 'to' => now()->toDateString()]) }}">QTD</a>
        </div>
    </div>

    <section class="kpis">
        @foreach($showKpis as $kpi)
            @include('components.kpi-card', [
                'label' => $kpi['label'],
                'value' => $kpi['value'],
                'change' => $kpi['change'] ?? 0,
                'icon' => $kpiIcons[$kpi['key'] ?? ''] ?? ($kpi['icon'] ?? 'bi-dot'),
            ])
        @endforeach
    </section>

    <section class="card hero">
        <div class="hero-top">
            <div>
                <div class="k">Leads acquired</div>
                <div class="v serif num">{{ number_format($totalKpi['value'] ?? 0) }}</div>
                <div class="delta">{{ ($totalKpi['change'] ?? 0) >= 0 ? '↑' : '↓' }} {{ number_format(abs($totalKpi['change'] ?? 0), 1) }}% vs previous period</div>
            </div>
            <span class="quiet">Daily volume · {{ \Carbon\Carbon::parse($from)->format('j M') }}–{{ \Carbon\Carbon::parse($to)->format('j M Y') }}</span>
        </div>
        <div class="chart">
            <svg viewBox="0 0 720 196" preserveAspectRatio="none" aria-hidden="true">
                <defs>
                    <linearGradient id="fill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#161513" stop-opacity=".16"/>
                        <stop offset="100%" stop-color="#161513" stop-opacity="0"/>
                    </linearGradient>
                    <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                        <feGaussianBlur stdDeviation="2.2" result="b"/>
                        <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                    </filter>
                </defs>
                <line x1="0" y1="49" x2="720" y2="49" stroke="#efebe3"/>
                <line x1="0" y1="98" x2="720" y2="98" stroke="#efebe3"/>
                <line x1="0" y1="147" x2="720" y2="147" stroke="#efebe3"/>
                <path d="M0,138 C48,134 96,126 144,118 C192,110 240,102 288,108 C336,114 384,82 432,72 C480,62 528,68 576,50 C624,32 672,42 720,30 L720,196 L0,196 Z" fill="url(#fill)"/>
                <path d="M0,138 C48,134 96,126 144,118 C192,110 240,102 288,108 C336,114 384,82 432,72 C480,62 528,68 576,50 C624,32 672,42 720,30" fill="none" stroke="#161513" stroke-width="2.4" stroke-linecap="round" filter="url(#glow)"/>
                <circle cx="720" cy="30" r="5" fill="#161513"/>
                <circle cx="720" cy="30" r="9" fill="#161513" opacity=".16"/>
            </svg>
        </div>
        <div class="axis">
            <span>{{ \Carbon\Carbon::parse($from)->format('j M') }}</span>
            <span>{{ \Carbon\Carbon::parse($from)->copy()->addDays(max(1, (int) round($span / 4)))->format('j M') }}</span>
            <span>{{ \Carbon\Carbon::parse($from)->copy()->addDays(max(1, (int) round($span / 2)))->format('j M') }}</span>
            <span>{{ \Carbon\Carbon::parse($from)->copy()->addDays(max(1, (int) round($span * 3 / 4)))->format('j M') }}</span>
            <span>{{ \Carbon\Carbon::parse($to)->format('j M') }}</span>
        </div>
    </section>

    <section class="grid-2">
        <article class="card">
            <div class="card-h"><h2>Lead pipeline</h2><span>New → won</span></div>
            <div class="pipe">
                <div class="conv"><b class="serif">{{ $conv }}% conversion</b><span>{{ number_format($lastStage['count'] ?? 0) }} closed this period</span></div>
                <div class="mix">
                    @forelse($data['pipeline'] as $row)
                        <i style="width:{{ max(2, $row['percent']) }}%;background:{{ $row['color'] ?: '#161513' }}"></i>
                    @empty
                        <i style="width:100%;background:#e6e1d8"></i>
                    @endforelse
                </div>
                <div class="stages">
                    @foreach($data['pipeline'] as $row)
                        <div class="st">
                            <span class="sw" style="background:{{ $row['color'] ?: '#161513' }}"></span>
                            <b>{{ $row['name'] }}</b>
                            <span class="c num">{{ number_format($row['count']) }}</span>
                            <span class="p">{{ $row['percent'] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="card">
            <div class="card-h"><h2>Lead sources</h2><span>Share of volume</span></div>
            @forelse($data['sources'] as $row)
                @php $slug = strtolower($row->source->slug ?? 'other'); @endphp
                <div class="src">
                    <div>@include('components.source-icon', ['source' => $row->source])</div>
                    <div>
                        <b>{{ $row->source->name ?? 'Unknown' }}</b>
                        <div class="bar"><i style="width: {{ ($row->total / $sourceMax) * 100 }}%"></i></div>
                    </div>
                    <b class="num">{{ $row->total }}</b>
                    <span class="pct">{{ round(($row->total / $sourceTotal) * 100) }}%</span>
                </div>
            @empty
                <div class="empty-state py-4">No source data yet.</div>
            @endforelse
        </article>
    </section>

    <section class="grid-3">
        <article class="card">
            <div class="card-h"><h2>Lead hotspots</h2><a class="quiet" href="{{ route('crm.reports.index') }}">View map</a></div>
            <div class="map-wrap">
                <div class="map">
                    @if($data['locations']->whereNotNull('latitude')->count())
                        <div id="hotspotMap" style="height:108px;border-radius:11px"></div>
                    @else
                        <svg viewBox="0 0 280 132" aria-hidden="true">
                            <path d="M32,82 C48,38 96,22 142,30 C188,38 218,20 252,42 C266,52 260,88 232,100 C188,118 138,110 92,104 C58,100 22,104 32,82Z" fill="#ddd6c8" stroke="#d0c8b8"/>
                        </svg>
                    @endif
                </div>
            </div>
            <div class="locs">
                @forelse($data['locations']->take(4) as $loc)
                    <div class="loc">{{ $loc->area ? $loc->area.', ' : '' }}{{ $loc->city }} <span class="num">{{ $loc->total }}</span></div>
                @empty
                    <div class="loc">No location data yet <span class="num">0</span></div>
                @endforelse
            </div>
        </article>

        <article class="card">
            <div class="card-h"><h2>AI Copilot</h2><span>Beta</span></div>
            <div class="insight">
                <div class="feature">
                    <div class="tag">Recommended</div>
                    <b>{{ $insight['title'] ?? 'No insight yet' }}</b>
                    <p>{{ $insight['body'] ?? 'AI recommendations appear as your workspace gathers data.' }}</p>
                    <div class="actions">
                        <a class="primary" href="{{ route('crm.ai.index') }}">Apply</a>
                        <a class="ghost" href="{{ route('crm.ai.index') }}">Dismiss</a>
                    </div>
                </div>
                @if($insight2)
                    <div class="mini"><span>Attention</span><b>{{ $insight2['title'] }}</b><p>{{ $insight2['body'] }}</p></div>
                @endif
                @if($insight3)
                    <div class="mini"><span>Window</span><b>{{ $insight3['title'] }}</b><p>{{ $insight3['body'] }}</p></div>
                @endif
            </div>
        </article>

        <article class="card">
            <div class="card-h"><h2>Recent activity</h2><a class="quiet" href="{{ route('crm.leads.index') }}">View all</a></div>
            <div class="feed">
                @forelse($data['activities']->take(4) as $act)
                    @php
                        $type = $act->type ?? 'note';
                        $cls = ['whatsapp'=>'wa','call'=>'call','email'=>'mail','meeting'=>'mail','note'=>'note'][$type] ?? 'note';
                        $ico = ['whatsapp'=>'bi-whatsapp','call'=>'bi-telephone','email'=>'bi-envelope','meeting'=>'bi-calendar-event','note'=>'bi-sticky'][$type] ?? 'bi-activity';
                    @endphp
                    <div class="item">
                        <div class="t {{ $cls }}"><i class="bi {{ $ico }}"></i></div>
                        <div>
                            <b>{{ $act->subject }}{{ $act->lead ? ' · '.$act->lead->full_name : '' }}</b>
                            <small>{{ optional($act->activity_at)->diffForHumans() }}</small>
                        </div>
                    </div>
                @empty
                    <div class="item"><div class="t note"><i class="bi bi-sticky"></i></div><div><b>No recent activity</b><small>New events will show here</small></div></div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid-2" style="margin-top:10px">
        <article class="card">
            <div class="card-h"><h2>Top performing users</h2><a class="quiet" href="{{ route('crm.users.index') }}">View team</a></div>
            <div class="team">
                @forelse($data['topUsers']->take(3) as $i => $row)
                    <div class="agent">
                        <span class="rank">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="avatar" style="width:30px;height:30px;display:grid;place-items:center;background:var(--ink);color:#f6f1e8;font-size:11px;font-weight:700">{{ $row['user']->initials() }}</div>
                        <div class="who"><b>{{ $row['user']->name }}</b><small>{{ $row['user']->roleName() }}</small></div>
                        <div class="m num">{{ $row['closed'] }} won<small>{{ $row['conversion'] }}% conv.</small></div>
                    </div>
                @empty
                    <div class="agent"><div class="who"><b>No team data yet</b><small>Closed deals will rank here</small></div></div>
                @endforelse
            </div>
        </article>
        <article class="card">
            <div class="card-h">
                <h2>Likely closings this week</h2>
                <span class="num">{{ auth()->user()->organization->formatMoney($data['closings']->sum('estimated_value')) }} · {{ $data['closings']->count() }} deals</span>
            </div>
            @forelse($data['closings'] as $deal)
                <div class="deal">
                    <div class="r">{{ $deal->name }}<span class="num">{{ auth()->user()->organization->formatMoney($deal->estimated_value) }}</span></div>
                    <small>{{ $deal->lead->full_name ?? '' }} · {{ optional($deal->expected_close_date)->toFormattedDateString() }} · {{ (int) $deal->probability }}% likely</small>
                    <div class="pbar"><i style="width:{{ (int) $deal->probability }}%"></i></div>
                </div>
            @empty
                <div class="deal"><div class="r">No expected closings</div><small>Open deals with close dates will appear here</small></div>
            @endforelse
        </article>
    </section>

    <div class="card tasks">
        <div class="s">
            <span><b>{{ $data['tasks']['active'] }}</b> active tasks</span>
            <span><b>{{ $data['tasks']['unread'] }}</b> unread</span>
            <span><b>{{ $data['tasks']['unresponded'] }}</b> unresponded</span>
            <span><b>{{ $data['tasks']['overdue'] }}</b> overdue</span>
        </div>
        <a href="{{ route('crm.ai.index') }}">Open smart tasks →</a>
    </div>
</main>
@endsection
@push('scripts')
@if($data['locations']->whereNotNull('latitude')->count())
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('hotspotMap').setView([{{ $data['locations']->first()->latitude ?? 31.52 }}, {{ $data['locations']->first()->longitude ?? 74.35 }}], 11);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'© OpenStreetMap'}).addTo(map);
@foreach($data['locations']->whereNotNull('latitude') as $loc)
L.circle([{{ $loc->latitude }}, {{ $loc->longitude }}], {radius: {{ max(200, $loc->total*80) }}, color:'#161513'}).addTo(map);
@endforeach
</script>
@endif
@endpush
