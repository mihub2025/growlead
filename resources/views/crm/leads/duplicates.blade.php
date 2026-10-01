@extends('layouts.crm')
@section('title', 'Duplicate Review')
@section('content')
@include('components.page-header', ['title' => 'Duplicate Review', 'subtitle' => 'Review and merge possible duplicate leads.'])
<div class="crm-content">
    <div class="card-crm table-wrap">
        <table class="table-crm">
            <thead><tr><th>Lead</th><th>Possible Duplicate</th><th>Confidence</th><th>Fields</th><th></th></tr></thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td><a href="{{ route('crm.leads.show', $item->lead) }}">{{ $item->lead->full_name }}</a></td>
                    <td><a href="{{ route('crm.leads.show', $item->duplicate) }}">{{ $item->duplicate->full_name }}</a></td>
                    <td>{{ $item->confidence }}%</td>
                    <td>{{ implode(', ', $item->matching_fields ?? []) }}</td>
                    <td>
                        <form method="POST" action="{{ route('crm.leads.merge', $item->lead) }}">
                            @csrf
                            <input type="hidden" name="duplicate_id" value="{{ $item->possible_duplicate_id }}">
                            <button class="btn btn-sm btn-primary" data-confirm="Merge these leads?">Merge</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-state">No pending duplicates.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $items->links() }}</div>
    </div>
</div>
@endsection
