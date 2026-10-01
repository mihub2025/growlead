@php
    $ok = in_array($log->status, ['processed', 'queued', 'received', 'success'], true);
    $sourceKey = str_replace(['-ads', '-forms'], '', (string) $log->source);
    $sourceName = $providers[$log->source] ?? $providers[$sourceKey] ?? \Illuminate\Support\Str::headline(str_replace('-', ' ', $log->source ?: 'Unknown'));
    $response = $ok ? ($log->status === 'queued' ? '202 Accepted' : '200 OK') : '500 Internal Server Error';
@endphp
<tr class="int-log-row" role="button" tabindex="0"
    data-bs-toggle="modal" data-bs-target="#logModal"
    data-time="{{ optional($log->created_at)->format('M j, Y g:i A') }}"
    data-source="{{ $sourceName }}"
    data-status="{{ $ok ? 'Success' : 'Failed' }}"
    data-response="{{ $response }}"
    data-result="{{ $log->result }}"
    data-error="{{ $log->error }}"
    data-payload="{{ json_encode($log->payload ?? []) }}">
    <td>{{ optional($log->created_at)->format('M j, Y g:i A') }}</td>
    <td>
        <span class="int-log-source">
            @include('components.source-icon', ['slug' => $log->source ?: $sourceKey])
            {{ $sourceName }}
        </span>
    </td>
    <td>
        <span class="int-log-status">
            <span class="dot {{ $ok ? 'ok' : 'bad' }}"></span>
            {{ $ok ? 'Success' : 'Failed' }}
        </span>
    </td>
    <td>
        <span class="int-resp {{ $ok ? 'ok' : 'bad' }}">{{ $response }}</span>
    </td>
    <td class="int-log-chevron"><i class="bi bi-chevron-right"></i></td>
</tr>
