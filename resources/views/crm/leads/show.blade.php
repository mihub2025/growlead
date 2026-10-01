@extends('layouts.crm')
@section('title', 'Lead Detail')
@php
    $created = $lead->meta_created_at ?: $lead->created_at;
    $phoneDigits = preg_replace('/\D+/', '', (string) ($lead->whatsapp ?: $lead->phone));
    $metaRows = $lead->isMetaLead() ? $lead->metaFormRows() : [];
    $payload = $lead->metaFormPayload();
    $pageName = $payload['page_name'] ?? null;
    if (! $pageName && filled($lead->notes) && preg_match('/^Page:\s*(.+)$/mi', (string) $lead->notes, $m)) {
        $pageName = trim($m[1]);
    }
@endphp
@section('content')
<div class="crm-content ld-page">
    <a class="ld-back" href="{{ route('crm.leads.index') }}"><i class="bi bi-arrow-left"></i> Back to Leads</a>
    <div class="ld-head">
        <h1 class="crm-title">Lead Detail</h1>
        <div class="crm-actions">
            <a href="{{ route('crm.leads.activity', $lead) }}" class="btn btn-outline-soft">View Activity</a>
            <a href="{{ route('crm.leads.edit', $lead) }}" class="btn btn-primary">Edit</a>
            <div class="dropdown">
                <button class="btn btn-outline-soft" type="button" data-bs-toggle="dropdown" aria-label="More actions">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <form method="POST" action="{{ route('crm.leads.destroy', $lead) }}" data-confirm="Delete this lead?">
                            @csrf @method('DELETE')
                            <button class="dropdown-item text-danger" type="submit">Delete lead</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card-crm ld-hero">
        <div class="ld-hero-id">
            <div class="ld-avatar">{{ $lead->initials() }}</div>
            <div>
                <div class="ld-name">
                    {{ $lead->full_name }}
                    <span class="badge-soft badge-hot">{{ $lead->scoreLabel() }}</span>
                </div>
                <div class="ld-contact">
                    <span><i class="bi bi-envelope"></i> {{ $lead->email ?: '—' }}</span>
                    <span><i class="bi bi-telephone"></i> {{ $lead->phone ?: '—' }}</span>
                </div>
            </div>
        </div>
        <div class="ld-facts">
            <div>
                <small>Lead Source</small>
                <strong class="ld-source">
                    @include('components.source-icon', ['slug' => $lead->source->slug ?? 'meta'])
                    {{ $lead->source->name ?? '—' }}
                </strong>
            </div>
            <div>
                <small>Assigned Agent</small>
                <strong>{{ $lead->assignedUser->name ?? 'Unassigned' }}</strong>
            </div>
            <div>
                <small>Location</small>
                <strong>{{ $lead->location ?: '—' }}</strong>
            </div>
            <div>
                <small>Lead Created</small>
                <strong>{{ optional($created)->format('M j, Y') ?: '—' }}</strong>
            </div>
            <div>
                <small>Interest</small>
                <strong>{{ $lead->interested_in ?: '—' }}</strong>
            </div>
            <div>
                <small>Status</small>
                <strong><span class="badge-soft badge-active">{{ $lead->stage->name ?? $lead->statusLabel() }}</span></strong>
            </div>
        </div>
    </div>

    <div class="ld-kpis">
        @foreach([
            ['Lead Score', ($lead->lead_score ?? 0).' / 100', $lead->lead_score >= 80 ? 'Excellent' : 'Fair', 'bi-speedometer2', ''],
            ['Sentiment', $lead->sentimentEmoji().' '.$lead->sentimentLabel(), 'Last 7 days', 'bi-emoji-smile', $lead->sentimentClass()],
            ['Engagement', is_numeric($lead->engagement_score) ? $lead->engagement_score : ($lead->engagement_score ?: 'High'), 'Activity trend', 'bi-activity', ''],
            ['Duplicate Risk', ucfirst($duplicateRisk['level'] ?? 'Low'), 'Match check', 'bi-shield-check', ''],
            ['Probability to Close', ($lead->conversion_probability ?? $lead->lead_score ?? 0).'%', 'Forecast', 'bi-graph-up', ''],
        ] as $m)
        <div class="card-crm ld-kpi">
            <div class="ld-kpi-top">
                <span>{{ $m[0] }}</span>
                <i class="bi {{ $m[3] }}"></i>
            </div>
            <b class="{{ $m[4] }}">{{ $m[1] }}</b>
            <small>{{ $m[2] }}</small>
        </div>
        @endforeach
    </div>

    <div class="ld-grid">
        <div>
            @if($lead->isMetaLead())
            <div class="card-crm ld-card">
                <div class="card-head"><h5>Lead Source & Meta Context</h5></div>
                <dl class="ld-dl">
                    <div><dt>Source</dt><dd>{{ $lead->source->name ?? 'Meta Lead Ads' }}</dd></div>
                    <div><dt>Page</dt><dd>{{ $pageName ?: '—' }}</dd></div>
                    <div><dt>Form Name</dt><dd>{{ $payload['form_name'] ?? $lead->interested_in ?: '—' }}</dd></div>
                    <div><dt>Created</dt><dd>{{ optional($created)->format('M j, Y') ?: '—' }}</dd></div>
                    <div><dt>Status</dt><dd><span class="badge-soft badge-active">{{ $lead->stage->name ?? $lead->statusLabel() }}</span></dd></div>
                </dl>
            </div>
            @endif

            <div class="card-crm ld-card">
                <div class="card-head"><h5><i class="bi bi-stars"></i> AI Summary</h5></div>
                <p>{{ $summary }}</p>
                <a class="ld-link" href="#timeline">View Full Summary →</a>
            </div>

            <div class="card-crm ld-card ld-next">
                <h6>Recommended Next Step</h6>
                <p>{{ $action['label'] ?? 'Make first contact' }}</p>
                <a href="{{ route('crm.leads.edit', $lead) }}" class="btn btn-primary btn-sm">Schedule Follow-up</a>
            </div>

            <div class="card-crm ld-card">
                <h6>Suggested Responses</h6>
                <div class="ld-draft">{{ $whatsappDraft }}</div>
                @if(! ($lead->whatsapp || $lead->phone))
                    <div class="ld-warn">WhatsApp number required</div>
                @endif
                <div class="ld-draft">{{ $emailDraft }}</div>
                @if($lead->email)
                    <a class="btn btn-primary btn-sm" href="mailto:{{ $lead->email }}">Send via Email</a>
                @endif
            </div>
        </div>

        <div>
            @if($lead->isMetaLead() && $metaRows)
            <div class="card-crm ld-card">
                <div class="card-head"><h5>Meta Lead Form Submission Details</h5></div>
                <div class="table-wrap">
                    <table class="table-crm ld-meta-table">
                        <thead>
                            <tr>
                                <th>Field Label</th>
                                <th>Submitted Value</th>
                                <th>Meta Key</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($metaRows as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td>{{ $row['value'] }}</td>
                                <td><code>{{ $row['key'] }}</code></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="ld-note">Values are captured from the Meta Lead Ads form submission.</p>
            </div>
            @endif

            <div class="card-crm ld-card" id="timeline">
                <div class="card-head d-flex justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">Conversation Timeline</h5>
                    <a href="{{ route('crm.leads.activity', $lead) }}" class="ld-link">View all activity →</a>
                </div>
                <div class="timeline">
                    @forelse($lead->activities->take(4) as $act)
                    <div class="timeline-item">
                        <strong>{{ $act->subject }}</strong>
                        <div class="small text-muted">{{ $act->description }} · {{ optional($act->activity_at)->diffForHumans() }}</div>
                        <div class="act-agent mt-1">
                            <span class="avatar" style="width:22px;height:22px;font-size:10px">{{ $act->user?->initials() ?: 'SY' }}</span>
                            <span>{{ $act->user->name ?? 'System' }}</span>
                        </div>
                        @if($loop->first)
                        <div class="ld-quick">
                            @if($lead->phone)<a class="btn btn-outline-soft btn-sm" href="tel:{{ $lead->phone }}">Call</a>@endif
                            @if($lead->email)<a class="btn btn-outline-soft btn-sm" href="mailto:{{ $lead->email }}">Email</a>@endif
                            @if($phoneDigits)<a class="btn btn-outline-soft btn-sm" href="https://wa.me/{{ $phoneDigits }}" target="_blank" rel="noopener">WhatsApp</a>@endif
                            <a class="btn btn-outline-soft btn-sm" href="{{ route('crm.leads.activity', $lead) }}">Activity details</a>
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="text-muted">No activity yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div>
            <div class="card-crm ld-card">
                <div class="card-head"><h5>Notes</h5></div>
                @foreach($lead->notesList as $note)
                    <div class="ld-note-item">
                        <div>{{ $note->note }}</div>
                        <small>{{ $note->user->name ?? '' }} · {{ $note->created_at->diffForHumans() }}</small>
                    </div>
                @endforeach
                <form method="POST" action="{{ route('crm.leads.notes', $lead) }}">
                    @csrf
                    <textarea name="note" class="form-control mb-2" required></textarea>
                    <button class="btn btn-primary btn-sm">Add note</button>
                </form>
            </div>
            <div class="card-crm ld-card">
                <div class="card-head"><h5>Documents</h5></div>
                @forelse($lead->attachments as $file)
                    <div class="d-flex justify-content-between py-1"><span><i class="bi bi-file-earmark"></i> {{ $file->original_filename }}</span><small>{{ $file->humanSize() }}</small></div>
                @empty
                    <div class="text-muted mb-2">No files yet</div>
                @endforelse
                <a href="{{ route('crm.leads.edit', $lead) }}" class="btn btn-outline-soft btn-sm">Upload</a>
            </div>
            <div class="card-crm ld-card">
                <div class="card-head"><h5>Tasks</h5></div>
                @foreach($lead->tasks as $task)
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <span>{{ $task->title }}</span>
                        @if($task->status!=='completed')
                            <form method="POST" action="{{ route('crm.tasks.complete', $task) }}">@csrf<button class="btn btn-sm btn-outline-soft">Done</button></form>
                        @else
                            <span class="badge-soft badge-active">Done</span>
                        @endif
                    </div>
                @endforeach
                <form method="POST" action="{{ route('crm.tasks.store') }}" class="mt-2">
                    @csrf
                    <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                    <input name="title" class="form-control mb-2" placeholder="New task" required>
                    <button class="btn btn-outline-soft btn-sm">Add task</button>
                </form>
            </div>
            <div class="card-crm ld-card">
                <div class="card-head"><h5>Opportunities</h5></div>
                @foreach($lead->opportunities as $opp)
                    <div class="d-flex justify-content-between py-1"><span>{{ $opp->name }}</span><span>{{ $opp->status }} · {{ $opp->probability }}%</span></div>
                @endforeach
                <form method="POST" action="{{ route('crm.opportunities.store') }}" class="mt-2">
                    @csrf
                    <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                    <input name="name" class="form-control mb-2" placeholder="Opportunity name" required>
                    <input name="estimated_value" class="form-control mb-2" placeholder="Value">
                    <button class="btn btn-outline-soft btn-sm">Add opportunity</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
