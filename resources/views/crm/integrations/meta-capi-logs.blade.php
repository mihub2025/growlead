@extends('layouts.crm')
@section('title', 'Meta CAPI event logs')
@section('content')
@php
    $canManage = auth()->user()->hasPermission('integrations.manage');
@endphp
<div class="crm-content">
    <div class="crm-topbar">
        <div>
            <a class="fb-biz-back" href="{{ route('crm.integrations.meta.capi') }}"><i class="bi bi-arrow-left"></i> Back to Conversions API</a>
            <p class="kicker mt-2">Meta Ads</p>
            <h1 class="crm-title">CAPI event logs</h1>
            <p class="crm-subtitle">CRM lifecycle events queued and sent to Meta Conversions API.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card-crm">
        <div class="table-wrap">
            <table class="table-crm">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>CRM Status</th>
                        <th>Meta Event</th>
                        <th>Event Time</th>
                        <th>Sent Time</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Response / Error</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($events as $event)
                    <tr>
                        <td>
                            @if($event->lead)
                                <a href="{{ route('crm.leads.show', $event->lead) }}">{{ $event->lead->full_name }}</a>
                                @if($event->is_test)<span class="badge-soft badge-warm ms-1">Test</span>@endif
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $event->crm_status }}</td>
                        <td>{{ $event->meta_event_name }}</td>
                        <td>{{ $event->event_time ? \Illuminate\Support\Carbon::createFromTimestamp($event->event_time)->timezone(config('app.timezone'))->format('M j, Y g:i A') : '—' }}</td>
                        <td>{{ $event->sent_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}</td>
                        <td>
                            @php
                                $cls = match($event->status) {
                                    'sent' => 'badge-active',
                                    'failed' => 'badge-neutral',
                                    'skipped' => 'badge-warm',
                                    default => 'badge-done',
                                };
                            @endphp
                            <span class="badge-soft {{ $cls }}">{{ $event->statusLabel() }}</span>
                        </td>
                        <td>{{ $event->attempts }}</td>
                        <td class="small text-muted" style="max-width:280px">
                            @if($event->error_message)
                                {{ \Illuminate\Support\Str::limit($event->error_message, 120) }}
                            @elseif($event->response_code)
                                HTTP {{ $event->response_code }}
                                @if(isset($event->response_body_sanitized['events_received']))
                                    · received {{ $event->response_body_sanitized['events_received'] }}
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($canManage && $event->isRetryable())
                                <form method="POST" action="{{ route('crm.integrations.meta.capi.retry', $event) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-soft">Retry</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No CAPI events yet. Mark a Meta lead as Qualified to enqueue one.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $events->links() }}</div>
    </div>
</div>
@endsection
