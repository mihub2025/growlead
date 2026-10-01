@extends('layouts.crm')
@section('title', 'Lead Activity')
@php
    $phoneDigits = preg_replace('/\D+/', '', (string) ($lead->whatsapp ?: $lead->phone));
    $activityCount = $lead->activities->count();
    $typeMeta = [
        'call' => ['icon' => 'bi-telephone', 'tone' => 'call'],
        'whatsapp' => ['icon' => 'bi-whatsapp', 'tone' => 'whatsapp'],
        'email' => ['icon' => 'bi-envelope', 'tone' => 'email'],
        'note' => ['icon' => 'bi-journal-text', 'tone' => 'note'],
        'meeting' => ['icon' => 'bi-people', 'tone' => 'meeting'],
        'appointment' => ['icon' => 'bi-calendar-event', 'tone' => 'meeting'],
        'follow_up' => ['icon' => 'bi-arrow-repeat', 'tone' => 'note'],
        'sms' => ['icon' => 'bi-chat-dots', 'tone' => 'whatsapp'],
        'stage_changed' => ['icon' => 'bi-diagram-3', 'tone' => 'stage'],
        'status_changed' => ['icon' => 'bi-flag', 'tone' => 'status'],
        'lead_created' => ['icon' => 'bi-person-plus', 'tone' => 'created'],
    ];
@endphp
@section('content')
<div class="crm-content act-page">
    <a class="ld-back" href="{{ route('crm.leads.index') }}"><i class="bi bi-arrow-left"></i> Back to Leads</a>
    <div class="ld-head">
        <div>
            <p class="kicker">Lead workspace</p>
            <h1 class="crm-title">Activity</h1>
        </div>
        <div class="crm-actions">
            <a href="{{ route('crm.leads.show', $lead) }}" class="btn btn-outline-soft">View Lead</a>
            <a href="{{ route('crm.leads.edit', $lead) }}" class="btn btn-primary">Edit</a>
        </div>
    </div>

    <section class="card-crm act-hero">
        <div class="act-hero-lead">
            <div class="ld-avatar">{{ $lead->initials() }}</div>
            <div class="min-w-0">
                <div class="ld-name">
                    {{ $lead->full_name }}
                    <span class="badge-soft {{ $lead->statusBadgeClass() }}">{{ $lead->statusLabel() }}</span>
                </div>
                <div class="act-hero-meta">
                    <span><i class="bi bi-person"></i> {{ $lead->assignedUser->name ?? 'Unassigned' }}</span>
                    <span><i class="bi bi-telephone"></i> {{ $lead->phone ?: '—' }}</span>
                </div>
                <div class="ld-quick">
                    @if($lead->phone)<a class="btn btn-outline-soft btn-sm" href="tel:{{ $lead->phone }}"><i class="bi bi-telephone"></i> Call</a>@endif
                    @if($phoneDigits)<a class="btn btn-outline-soft btn-sm" href="https://wa.me/{{ $phoneDigits }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> WhatsApp</a>@endif
                    @if($lead->email)<a class="btn btn-outline-soft btn-sm" href="mailto:{{ $lead->email }}"><i class="bi bi-envelope"></i> Email</a>@endif
                </div>
            </div>
        </div>
        <div class="act-hero-stats">
            <div class="act-stat">
                <div class="act-stat-label">Last Activity</div>
                @if($lastActivity)
                    <div class="act-stat-value">{{ $lastActivity->subject ?: \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $lastActivity->type)) }}</div>
                    <div class="act-stat-sub">{{ $lastActivity->user->name ?? 'System' }} · {{ optional($lastActivity->activity_at)->diffForHumans() }}</div>
                @else
                    <div class="act-stat-value">None yet</div>
                    <div class="act-stat-sub">Log the first action</div>
                @endif
            </div>
            <div class="act-stat {{ $nextTask && $nextTask->isOverdue() ? 'is-overdue' : '' }}">
                <div class="act-stat-label">Next Task</div>
                @if($nextTask)
                    <div class="act-stat-value">{{ $nextTask->title }}</div>
                    <div class="act-stat-sub">
                        {{ $nextTask->assignedUser->name ?? 'Unassigned' }}
                        · {{ optional($nextTask->due_at)->diffForHumans() ?: 'No due date' }}
                        @if($nextTask->isOverdue()) <span class="act-overdue">Overdue</span> @endif
                    </div>
                @else
                    <div class="act-stat-value">No task</div>
                    <div class="act-stat-sub">Schedule a follow-up</div>
                @endif
            </div>
            <div class="act-stat">
                <div class="act-stat-label">Agent Activity</div>
                <div class="act-stat-value">{{ $activityCount }}</div>
                <div class="act-stat-sub">{{ $activityCount === 1 ? 'logged action' : 'logged actions' }}</div>
            </div>
        </div>
    </section>

    <div class="act-layout">
        <section class="card-crm act-panel">
            <div class="act-panel-h">
                <h5>All activity</h5>
                <span>{{ $activityCount }} {{ $activityCount === 1 ? 'event' : 'events' }}</span>
            </div>
            <div class="act-feed">
                @forelse($lead->activities as $act)
                    @php
                        $typeKey = strtolower((string) $act->type);
                        $meta = $typeMeta[$typeKey] ?? ['icon' => 'bi-dot', 'tone' => 'other'];
                        $title = $act->subject ?: \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $act->type));
                        $outcome = data_get($act->metadata, 'outcome');
                    @endphp
                    <article class="act-feed-item">
                        <div class="act-feed-ico {{ $meta['tone'] }}"><i class="bi {{ $meta['icon'] }}"></i></div>
                        <div class="act-feed-body">
                            <div class="act-feed-row">
                                <strong>{{ $title }}</strong>
                                <time>{{ optional($act->activity_at)->diffForHumans() ?: '—' }}</time>
                            </div>
                            @if($act->description)
                                <p class="act-feed-note">{{ $act->description }}</p>
                            @endif
                            @if($outcome)
                                <span class="act-outcome">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $outcome)) }}</span>
                            @endif
                            <div class="act-feed-who">
                                <span class="avatar">{{ $act->user?->initials() ?: 'SY' }}</span>
                                <span>{{ $act->user->name ?? 'System' }}</span>
                                <span>· {{ optional($act->activity_at)->format('M j, Y · g:i A') ?: '—' }}</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="act-empty">
                        <i class="bi bi-activity"></i>
                        <b>No activity yet</b>
                        <p>Log a call, note, or follow-up to start this lead’s timeline.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <aside class="act-side">
            <section class="card-crm act-panel">
                <div class="act-panel-h">
                    <h5>Log activity</h5>
                </div>
                <form method="POST" action="{{ route('crm.leads.activities', $lead) }}" class="act-form">
                    @csrf
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select mb-3">
                        @foreach(['call' => 'Call', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'note' => 'Note', 'meeting' => 'Meeting', 'appointment' => 'Appointment'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="form-label">What did the agent do?</label>
                    <textarea name="description" class="form-control mb-3" rows="3" placeholder="Outcome, notes, next step…"></textarea>
                    <button class="btn btn-primary w-100">Log activity</button>
                </form>
            </section>

            <section class="card-crm act-panel">
                <div class="act-panel-h">
                    <h5>Tasks</h5>
                    <span>{{ $lead->tasks->count() }}</span>
                </div>
                <div class="act-tasks">
                    @forelse($lead->tasks as $task)
                        <div class="act-task {{ $task->isOverdue() ? 'is-overdue' : '' }}">
                            <div>
                                <b>{{ $task->title }}</b>
                                <small>
                                    {{ $task->assignedUser->name ?? 'Unassigned' }}
                                    @if($task->due_at) · {{ $task->due_at->diffForHumans() }} @endif
                                    @if($task->isOverdue()) · Overdue @endif
                                </small>
                            </div>
                            @if($task->status !== 'completed')
                                <form method="POST" action="{{ route('crm.tasks.complete', $task) }}">@csrf<button class="btn btn-sm btn-outline-soft">Done</button></form>
                            @else
                                <span class="badge-soft badge-active">Done</span>
                            @endif
                        </div>
                    @empty
                        <div class="text-muted small py-2">No tasks yet.</div>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('crm.tasks.store') }}" class="act-form mt-3">
                    @csrf
                    <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                    <label class="form-label">New task</label>
                    <div class="act-task-add">
                        <input name="title" class="form-control" placeholder="Follow up, send proposal…" required>
                        <button class="btn btn-outline-soft">Add</button>
                    </div>
                </form>
            </section>
        </aside>
    </div>
</div>
@endsection
