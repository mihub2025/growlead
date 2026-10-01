@php
    $assignUsers = $users ?? collect();
@endphp
<div class="modal fade" id="assignAgentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content card-crm border-0" id="assignAgentsForm">
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Assign agents</h5>
                    <p class="text-muted small mb-0" id="assignAgentsSubtitle">They will only see leads from this campaign.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="search" class="form-control mb-3" id="assignAgentSearch" placeholder="Search agents…" autocomplete="off">
                <div class="assign-list">
                    @forelse($assignUsers as $u)
                        <label class="assign-row" data-search="{{ strtolower($u->name.' '.$u->email.' '.$u->roleName()) }}">
                            <input type="checkbox" name="user_ids[]" value="{{ $u->id }}">
                            <span class="avatar">{{ $u->initials() }}</span>
                            <span class="assign-meta">
                                <strong>{{ $u->name }}</strong>
                                <small>{{ $u->roleName() }}</small>
                            </span>
                        </label>
                    @empty
                        <div class="text-muted">No active users to assign.</div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-soft" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Save assignment</button>
            </div>
        </form>
    </div>
</div>
