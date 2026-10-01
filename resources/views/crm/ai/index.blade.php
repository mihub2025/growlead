@extends('layouts.crm')
@section('title', 'AI Copilot')
@section('content')
@php $predTotal = max(1, $high+$medium+$low); $prob = round(($high/$predTotal)*100); @endphp
<div class="crm-topbar">
    <div>
        <p class="kicker">Workspace</p>
        <h1 class="crm-title d-inline">AI Copilot <span class="badge-soft badge-score-mid">Beta</span></h1>
        <p class="crm-subtitle">Your AI-powered assistant for smarter sales, follow-ups, and automation.</p>
    </div>
    <form class="crm-actions" method="GET">
        <div class="date-range">
            <i class="bi bi-calendar3"></i>
            <input type="date" name="from" value="{{ request('from', now()->subDays(7)->toDateString()) }}" class="form-control" onchange="this.form.submit()">
            <span class="sep">–</span>
            <input type="date" name="to" value="{{ request('to', now()->toDateString()) }}" class="form-control" onchange="this.form.submit()">
        </div>
        <select name="source_id" class="form-select" style="width:150px"><option value="">All Sources</option>@foreach($sources as $s)<option value="{{ $s->id }}" @selected(request('source_id')==$s->id)>{{ $s->name }}</option>@endforeach</select>
        <a href="{{ route('crm.leads.export') }}" class="btn btn-outline-soft"><i class="bi bi-upload"></i> Export</a>
        <a href="{{ route('crm.automations.index') }}" class="btn btn-primary"><i class="bi bi-stars"></i> Create Workflow</a>
    </form>
</div>
<div class="crm-content">
    <div class="row g-3 mb-3">
        <div class="col">
            <div class="card-crm ai-stat">
                <div class="kpi-top"><div class="kpi-icon blue"><i class="bi bi-person"></i></div><div class="kpi-label">Priority Leads Today</div></div>
                <div class="kpi-value">{{ $priority->count() }}</div>
                <div class="kpi-change up">↑ 18% vs yesterday</div>
                <a class="more" href="{{ route('crm.leads.index', ['tab'=>'hot']) }}">View Leads →</a>
            </div>
        </div>
        <div class="col">
            <div class="card-crm ai-stat">
                <div class="kpi-top"><div class="kpi-icon green"><i class="bi bi-chat-dots"></i></div><div class="kpi-label">Suggested Follow-ups</div></div>
                <div class="kpi-value">{{ $followups->count() }}</div>
                <div class="kpi-change up">↑ 22% vs yesterday</div>
                <a class="more" href="{{ route('crm.leads.index', ['tab'=>'overdue']) }}">View All →</a>
            </div>
        </div>
        <div class="col">
            <div class="card-crm ai-stat">
                <div class="kpi-top"><div class="kpi-icon purple"><i class="bi bi-envelope"></i></div><div class="kpi-label">AI Message Drafts</div></div>
                <div class="kpi-value">{{ $priority->count() }}</div>
                <div class="hint">Ready to send</div>
                <a class="more" href="#assistant">View Drafts →</a>
            </div>
        </div>
        <div class="col">
            <div class="card-crm ai-stat">
                <div class="kpi-top"><div class="kpi-icon orange"><i class="bi bi-clock"></i></div><div class="kpi-label">Best Time to Contact</div></div>
                <div class="kpi-value" style="font-size:20px">Today, 4:00 PM</div>
                <div class="hint">High response window</div>
                <a class="more" href="{{ route('crm.reports.index') }}">View Insights →</a>
            </div>
        </div>
        <div class="col">
            <div class="card-crm ai-stat">
                <div class="kpi-top"><div class="kpi-icon red"><i class="bi bi-exclamation-triangle"></i></div><div class="kpi-label">Objection Risk</div></div>
                <div class="kpi-value">{{ $low }}</div>
                <div class="hint">High-risk conversations</div>
                <a class="more" href="{{ route('crm.leads.index', ['tab'=>'cold']) }}">View Analysis →</a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-xl-3">
                    <div class="card-crm p-3 h-100">
                        <h6 class="fw-bold mb-2">Hotspot Opportunities</h6>
                        @foreach($hotspots->take(4) as $h)
                            <div class="d-flex justify-content-between align-items-center py-1">
                                <span>{{ $h->city }}</span>
                                <span class="badge-soft {{ $h->total>=10?'badge-hot':'badge-warm' }}">{{ $h->total >= 10 ? 'High' : 'Medium' }} · {{ $h->total }}</span>
                            </div>
                        @endforeach
                        <div id="aiHotspotMap" class="map-mini mt-2"></div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card-crm p-3 h-100">
                        <h6 class="fw-bold mb-2">Conversion Predictions</h6>
                        <div class="semi-gauge"><canvas id="convGauge"></canvas><div class="val">{{ $prob }}%</div></div>
                        <div class="small mt-2">
                            <div class="d-flex justify-content-between"><span><span class="legend-dot" style="background:#16a34a"></span> High (70%+)</span><strong>{{ $high }}</strong></div>
                            <div class="d-flex justify-content-between"><span><span class="legend-dot" style="background:#f59e0b"></span> Medium (40–69%)</span><strong>{{ $medium }}</strong></div>
                            <div class="d-flex justify-content-between"><span><span class="legend-dot" style="background:#ef4444"></span> Low (&lt;40%)</span><strong>{{ $low }}</strong></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card-crm p-3 h-100">
                        <h6 class="fw-bold mb-2">Smart Tasks</h6>
                        <div class="task-line"><i class="bi bi-telephone" style="background:#1d4ed8"></i> Call Leads <strong class="ms-auto">{{ $followups->count() }}</strong></div>
                        <div class="task-line"><i class="bi bi-whatsapp" style="background:#16a34a"></i> Send WhatsApp <strong class="ms-auto">{{ $priority->count() }}</strong></div>
                        <div class="task-line"><i class="bi bi-arrow-repeat" style="background:#f59e0b"></i> Follow-ups <strong class="ms-auto">{{ $followups->count() }}</strong></div>
                        <div class="task-line"><i class="bi bi-calendar-event" style="background:#8b5cf6"></i> Schedule Visits <strong class="ms-auto">{{ max(1, (int) floor($followups->count()/2)) }}</strong></div>
                        <div class="task-line"><i class="bi bi-bell" style="background:#ef4444"></i> Pending Reminders <strong class="ms-auto">{{ $low }}</strong></div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card-crm p-3 h-100">
                        <h6 class="fw-bold mb-2">Automation Recommendations</h6>
                        <div class="auto-rec"><div class="fw-bold">Re-engage Inactive Leads</div><a href="{{ route('crm.automations.index') }}">Create Workflow</a></div>
                        <div class="auto-rec"><div class="fw-bold">Nurture New Leads</div><a href="{{ route('crm.automations.index') }}">Create Workflow</a></div>
                        <div class="auto-rec"><div class="fw-bold">Follow-up reminders</div><a href="{{ route('crm.automations.index') }}">Create Workflow</a></div>
                    </div>
                </div>
            </div>

            <div class="card-crm table-wrap">
                <div class="p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">Today's AI-Powered Task List <span class="text-muted fw-normal">({{ $followups->count() }} tasks)</span></h5>
                    <div class="d-flex gap-2">
                        <select class="form-select" style="width:auto"><option>All Task Types</option></select>
                        <select class="form-select" style="width:auto"><option>All Priorities</option></select>
                        <a href="{{ route('crm.leads.index', ['tab'=>'hot']) }}" class="btn btn-outline-soft"><i class="bi bi-stars"></i> Auto Prioritize</a>
                    </div>
                </div>
                <table class="table-crm">
                    <thead><tr><th></th><th>Priority</th><th>Lead / Contact</th><th>Task</th><th>Source</th><th>Due Time</th><th>Score</th><th>Actions</th></tr></thead>
                    <tbody>
                    @forelse($followups as $task)
                        @php $sc = (int) ($task->lead->lead_score ?? 0); @endphp
                        <tr>
                            <td><input type="checkbox"></td>
                            <td><span class="badge-soft {{ $task->priority==='high'?'badge-hot':($task->priority==='low'?'badge-done':'badge-warm') }}">{{ ucfirst($task->priority ?: 'medium') }}</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar">{{ $task->lead?->initials() ?: '—' }}</div>
                                    <div><strong>{{ $task->lead->full_name ?? '—' }}</strong><div class="small text-muted">{{ $task->lead->phone ?? '' }}</div></div>
                                </div>
                            </td>
                            <td><strong>{{ $task->title }}</strong><div class="small text-muted">{{ $task->lead->interested_in ?? '' }}</div></td>
                            <td>{{ $task->lead->source->name ?? '—' }}</td>
                            <td>{{ optional($task->due_at)->format('D, g:i A') ?: 'Today' }}</td>
                            <td><span class="score-box {{ $sc>=80?'hi':($sc>=60?'mid':'lo') }}">{{ $sc ?: '—' }}</span></td>
                            <td class="text-nowrap">
                                @if($task->lead)
                                    <a class="ico-btn tel" href="{{ route('crm.leads.show', $task->lead) }}"><i class="bi bi-telephone"></i></a>
                                    <a class="ico-btn wa" href="https://wa.me/{{ preg_replace('/\D+/','',$task->lead->whatsapp ?: $task->lead->phone) }}" target="_blank"><i class="bi bi-whatsapp"></i></a>
                                @endif
                                <span class="ico-btn"><i class="bi bi-calendar3"></i></span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">No tasks due today.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3 page-meta">Showing 1 to {{ $followups->count() }} of {{ $followups->count() }} tasks</div>
            </div>
        </div>

        <div class="col-xl-4" id="assistant">
            <div class="card-crm p-3 chat-panel chat-rail">
                <div class="card-head">
                    <h5><i class="bi bi-stars text-primary"></i> AI Copilot Assistant <span class="badge-soft badge-score-mid">Beta</span></h5>
                </div>
                <div class="msgs">
                    <div class="chat-user">Which leads should I focus on today?</div>
                    <div class="chat-ai">
                        @foreach($priority->take(3) as $i => $lead)
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="medal">{{ ['🥇','🥈','🥉'][$i] ?? '•' }}</span>
                                <div class="flex-grow-1">
                                    <strong>{{ $lead->full_name }}</strong>
                                    <div class="small text-muted">{{ $lead->interested_in ?: 'High intent' }} · {{ $lead->location ?: '—' }}</div>
                                </div>
                                <span class="score-box {{ $lead->lead_score>=80?'hi':'mid' }}">{{ $lead->lead_score }}</span>
                            </div>
                        @endforeach
                        <a href="{{ route('crm.leads.index', ['tab'=>'hot']) }}" class="btn btn-outline-soft btn-sm w-100 mt-1">View Full Priority List →</a>
                    </div>
                    <div class="chat-user">Draft a WhatsApp message{{ $draftLead ? ' for '.$draftLead->first_name : '' }}.</div>
                    <div class="chat-ai">
                        <div class="draft-box">{{ $draft }}</div>
                        <button class="btn btn-primary btn-sm w-100 mt-2" form="aiAsk" name="question" value="Generate a follow-up reply">Generate Reply</button>
                    </div>
                    @if(session('ai_question'))<div class="chat-user">{{ session('ai_question') }}</div>@endif
                    @if(session('ai_answer'))<div class="chat-ai">{{ session('ai_answer') }}</div>@endif
                </div>
                <form id="aiAsk" method="POST" action="{{ route('crm.ai.ask') }}" class="mt-3">
                    @csrf
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <button class="btn btn-outline-soft btn-sm" name="question" value="Draft a WhatsApp message">Send WhatsApp</button>
                        <button class="btn btn-outline-soft btn-sm" name="question" value="Schedule a follow-up task">Schedule Task</button>
                        <button class="btn btn-outline-soft btn-sm" name="question" value="Which leads should I focus on today?">More Options</button>
                    </div>
                    <div class="d-flex gap-2">
                        <input name="question" class="form-control" placeholder="Ask anything...">
                        <button class="btn btn-primary"><i class="bi bi-send-fill"></i></button>
                    </div>
                </form>
                <small class="text-muted d-block mt-2">AI responses can make mistakes. Please verify important details.</small>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
new Chart(document.getElementById('convGauge'), {
    type: 'doughnut',
    data: { datasets: [{ data: [{{ $prob }}, {{ 100-$prob }}], backgroundColor: ['#161513','#efebe3'], borderWidth: 0 }] },
    options: { rotation: -90, circumference: 180, cutout: '78%', plugins: { legend: { display: false }, tooltip: { enabled: false } }, responsive: true, maintainAspectRatio: false }
});
const map = L.map('aiHotspotMap', { zoomControl: false, attributionControl: false }).setView([31.52, 74.35], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
L.circle([31.52, 74.35], { radius: 2500, color: '#161513', fillOpacity: .25 }).addTo(map);
</script>
@endpush
