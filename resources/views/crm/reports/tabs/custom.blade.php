<div class="row g-3">
    <div class="col-xl-4">
        <div class="card-crm p-3">
            <h5 class="mb-2">Build a custom report</h5>
            <p class="text-muted small mb-3">Group the current date range and filters, then generate a table you can export.</p>
            <form method="GET">
                @foreach(request()->except(['group_by', 'tab']) as $key => $value)
                    @if(! is_array($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <input type="hidden" name="tab" value="custom">
                <label class="form-label">Group by</label>
                <select name="group_by" class="form-select mb-3">
                    @foreach(['source'=>'Source','campaign'=>'Campaign','agent'=>'Agent','city'=>'City','stage'=>'Pipeline stage','day'=>'Day'] as $key=>$label)
                        <option value="{{ $key }}" @selected(($data['groupBy'] ?? 'source')===$key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary w-100" type="submit">Generate report</button>
            </form>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card-crm p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Results by {{ $data['groupBy'] ?? 'source' }}</h5>
                <a href="{{ route('crm.reports.export', request()->query()) }}" class="btn btn-outline-soft btn-sm">Export CSV</a>
            </div>
            <table class="table-crm">
                <thead><tr><th>{{ ucfirst($data['groupBy'] ?? 'Group') }}</th><th>Leads</th><th>Qualified</th><th>Qualified rate</th></tr></thead>
                <tbody>
                @forelse($data['customRows'] as $row)
                    <tr>
                        <td class="fw-bold">{{ $row['label'] }}</td>
                        <td>{{ number_format($row['leads']) }}</td>
                        <td>{{ number_format($row['qualified']) }}</td>
                        <td>{{ $row['rate'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No rows for this grouping. Adjust filters or date range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
