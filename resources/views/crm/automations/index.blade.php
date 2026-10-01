@extends('layouts.crm')
@section('title', 'Automations')
@section('content')
@include('components.page-header', ['title' => 'Automations', 'subtitle' => 'Trigger actions when leads move, stall, or need follow-up.'])
<div class="crm-content">
    <div class="card-crm p-3 mb-3">
        <form method="POST" action="{{ route('crm.automations.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><input name="name" class="form-control" placeholder="Rule name" required></div>
            <div class="col-md-3">
                <select name="trigger" class="form-select">
                    @foreach(['lead_created','lead_assigned','stage_changed','status_changed','task_overdue','campaign_lead_received'] as $t)
                        <option value="{{ $t }}">{{ str_replace('_',' ', $t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-primary">Create Automation</button></div>
        </form>
    </div>
    <div class="card-crm table-wrap mb-3">
        <table class="table-crm">
            <thead><tr><th>Name</th><th>Trigger</th><th>Runs</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($rules as $rule)
                <tr>
                    <td>{{ $rule->name }}</td>
                    <td>{{ $rule->trigger }}</td>
                    <td>{{ $rule->runs_count }}</td>
                    <td><span class="badge-soft {{ $rule->status==='active'?'badge-active':'badge-neutral' }}">{{ ucfirst($rule->status) }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('crm.automations.destroy', $rule) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $rules->links() }}</div>
    </div>
    <div class="card-crm p-3">
        <h5>Automation Logs</h5>
        @foreach($runs as $run)
            <div class="d-flex justify-content-between border-bottom py-1"><span>{{ $run->rule->name ?? 'Rule' }} · {{ $run->status }}</span><small>{{ $run->created_at->diffForHumans() }}</small></div>
        @endforeach
    </div>
</div>
@endsection
