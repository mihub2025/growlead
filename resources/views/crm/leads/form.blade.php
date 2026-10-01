@extends('layouts.crm')
@section('title', $lead->exists ? 'Edit Lead' : 'Add Lead')
@section('content')
<div class="crm-topbar">
    <div>
        <p class="kicker">Lead record</p>
        <h1 class="crm-title d-inline">Add / Edit Lead</h1>
        <p class="crm-subtitle">Capture detailed lead information to build stronger relationships.</p>
    </div>
    <div class="crm-actions">
        <a href="{{ route('crm.leads.index') }}" class="btn btn-outline-soft">Cancel</a>
        <button form="leadForm" type="submit" class="btn btn-outline-soft"><i class="bi bi-save"></i> Save Draft</button>
        <button form="leadForm" class="btn btn-primary">{{ $lead->exists ? 'Update Lead' : 'Create Lead' }}</button>
    </div>
</div>
<div class="crm-content">
    <form id="leadForm" method="POST" action="{{ $lead->exists ? route('crm.leads.update', $lead) : route('crm.leads.store') }}" enctype="multipart/form-data">
        @csrf
        @if($lead->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card-crm section-card"><div class="section-title">1. Lead Information</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Stage</label><select name="pipeline_stage_id" class="form-select">@foreach($stages as $st)<option value="{{ $st->id }}" @selected(old('pipeline_stage_id', $lead->pipeline_stage_id)==$st->id)>{{ $st->name }}</option>@endforeach</select></div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            @php
                                $statusOptions = config('crm.default_lead_statuses', []);
                                $currentStatus = (string) old('status', $lead->status ?: 'not_contacted');
                                $matchedSlug = null;
                                foreach ($statusOptions as $row) {
                                    $slug = (string) ($row['slug'] ?? '');
                                    if ($slug !== '' && (
                                        strcasecmp($currentStatus, $slug) === 0
                                        || strcasecmp($currentStatus, (string) ($row['name'] ?? '')) === 0
                                    )) {
                                        $matchedSlug = $slug;
                                        break;
                                    }
                                    foreach ($row['aliases'] ?? [] as $alias) {
                                        if (strcasecmp($currentStatus, (string) $alias) === 0) {
                                            $matchedSlug = $slug;
                                            break 2;
                                        }
                                    }
                                }
                            @endphp
                            <select name="status" class="form-select">
                                @foreach($statusOptions as $row)
                                    <option value="{{ $row['slug'] }}" @selected($matchedSlug === $row['slug'])>{{ $row['name'] }}</option>
                                @endforeach
                                @if($matchedSlug === null && $currentStatus !== '')
                                    <option value="{{ $currentStatus }}" selected>{{ \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', $currentStatus)) }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Priority</label><select name="priority" class="form-select">@foreach(['low','medium','high','urgent'] as $p)<option value="{{ $p }}" @selected(old('priority', $lead->priority)==$p)>{{ ucfirst($p) }}</option>@endforeach</select></div>
                        <div class="col-md-8"><label class="form-label">Tags</label>
                            @php $selectedTags = old('tags', $lead->tags->pluck('id')->all()); @endphp
                            <div class="dropdown tag-dropdown">
                                <button class="form-select text-start" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <span class="tag-dropdown-label text-truncate">
                                        @php $selectedNames = $tags->whereIn('id', $selectedTags)->pluck('name'); @endphp
                                        {{ $selectedNames->isNotEmpty() ? $selectedNames->join(', ') : 'Select tags' }}
                                    </span>
                                </button>
                                <div class="dropdown-menu tag-dropdown-menu">
                                    @forelse($tags as $tag)
                                        <label class="tag-dropdown-item">
                                            <input type="checkbox" name="tags[]" value="{{ $tag->id }}" @checked(in_array($tag->id, $selectedTags))>
                                            <span class="tag-name tag-pill mb-0" style="background:{{ $tag->color }}20;color:{{ $tag->color }}">{{ $tag->name }}</span>
                                        </label>
                                    @empty
                                        <div class="text-muted small px-2 py-1">No tags yet. Add them in Settings.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Lead Score</label><input name="lead_score" value="{{ old('lead_score', $lead->lead_score) }}" class="form-control" placeholder="Auto"></div>
                    </div>
                </div>
                <div class="card-crm section-card"><div class="section-title">2. Contact Details</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">First Name *</label><input name="first_name" value="{{ old('first_name', $lead->first_name) }}" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Last Name</label><input name="last_name" value="{{ old('last_name', $lead->last_name) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $lead->email) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone', $lead->phone) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">WhatsApp</label><input name="whatsapp" value="{{ old('whatsapp', $lead->whatsapp) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Alternate Phone</label><input name="alternate_phone" value="{{ old('alternate_phone', $lead->alternate_phone) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Company</label><input name="company" value="{{ old('company', $lead->company) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Job Title</label><input name="job_title" value="{{ old('job_title', $lead->job_title) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Preferred Contact Method</label><input name="preferred_contact_method" value="{{ old('preferred_contact_method', $lead->preferred_contact_method) }}" class="form-control"></div>
                    </div>
                </div>
                <div class="card-crm section-card"><div class="section-title">3. Interest / Requirement</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Interested In</label><input name="interested_in" value="{{ old('interested_in', $lead->interested_in) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Category</label><input name="category" value="{{ old('category', $lead->category) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Quantity</label><input name="quantity" value="{{ old('quantity', $lead->quantity) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Purpose</label><input name="purpose" value="{{ old('purpose', $lead->purpose) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Decision Timeline</label><input name="decision_timeline" value="{{ old('decision_timeline', $lead->decision_timeline) }}" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Requirement</label><textarea name="requirement" class="form-control" rows="3">{{ old('requirement', $lead->requirement) }}</textarea></div>
                    </div>
                </div>
                <div class="card-crm section-card"><div class="section-title">4. Budget / Location</div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Min Budget</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ auth()->user()->organization->currencyCode() }}</span>
                                <input name="min_budget" value="{{ old('min_budget', $lead->min_budget) }}" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Budget</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ auth()->user()->organization->currencyCode() }}</span>
                                <input name="max_budget" value="{{ old('max_budget', $lead->max_budget) }}" class="form-control">
                            </div>
                            <input type="hidden" name="currency" value="{{ auth()->user()->organization->currencyCode() }}">
                        </div>
                        <div class="col-md-4"><label class="form-label">Country</label><input name="country" value="{{ old('country', $lead->country) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">City</label><input name="city" value="{{ old('city', $lead->city) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Area</label><input name="area" value="{{ old('area', $lead->area) }}" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">Latitude</label><input name="latitude" value="{{ old('latitude', $lead->latitude) }}" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">Longitude</label><input name="longitude" value="{{ old('longitude', $lead->longitude) }}" class="form-control"></div>
                    </div>
                </div>
                <div class="card-crm section-card"><div class="section-title">5. Source / Campaign</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Source</label><select name="source_id" class="form-select"><option value="">Select</option>@foreach($sources as $s)<option value="{{ $s->id }}" @selected(old('source_id', $lead->source_id)==$s->id)>{{ $s->name }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Campaign</label><select name="campaign_id" class="form-select"><option value="">Select</option>@foreach($campaigns as $c)<option value="{{ $c->id }}" @selected(old('campaign_id', $lead->campaign_id)==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Medium</label><input name="medium" value="{{ old('medium', $lead->medium) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">UTM Source</label><input name="utm_source" value="{{ old('utm_source', $lead->utm_source) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">UTM Medium</label><input name="utm_medium" value="{{ old('utm_medium', $lead->utm_medium) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">UTM Campaign</label><input name="utm_campaign" value="{{ old('utm_campaign', $lead->utm_campaign) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">External Lead ID</label><input name="external_id" value="{{ old('external_id', $lead->external_id) }}" class="form-control"></div>
                    </div>
                </div>
                <div class="card-crm section-card"><div class="section-title">6. Communication</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Preferred Language</label><input name="preferred_language" value="{{ old('preferred_language', $lead->preferred_language) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Best Contact Time</label><input name="preferred_contact_time" value="{{ old('preferred_contact_time', $lead->preferred_contact_time) }}" class="form-control"></div>
                        <div class="col-md-4 d-flex align-items-center justify-content-between"><span>Email Updates</span><label class="switch mb-0"><input type="hidden" name="email_opt_in" value="0"><input type="checkbox" name="email_opt_in" value="1" @checked(old('email_opt_in', $lead->email_opt_in ?? true))><span class="slider"></span></label></div>
                        <div class="col-md-4 d-flex align-items-center justify-content-between"><span>SMS Updates</span><label class="switch mb-0"><input type="hidden" name="sms_opt_in" value="0"><input type="checkbox" name="sms_opt_in" value="1" @checked(old('sms_opt_in', $lead->sms_opt_in ?? true))><span class="slider"></span></label></div>
                        <div class="col-md-4 d-flex align-items-center justify-content-between"><span>Do Not Contact</span><label class="switch mb-0"><input type="hidden" name="do_not_contact" value="0"><input type="checkbox" name="do_not_contact" value="1" @checked(old('do_not_contact', $lead->do_not_contact))><span class="slider"></span></label></div>
                    </div>
                </div>
                <div class="card-crm section-card"><div class="section-title">7. Notes</div>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Internal notes visible to the team">{{ old('notes', $lead->notes) }}</textarea>
                </div>
                <div class="card-crm section-card"><div class="section-title">8. Attachments</div>
                    <label class="dropzone w-100">
                        <i class="bi bi-cloud-arrow-up fs-3 d-block mb-1"></i>
                        Drag and drop files or <span class="text-primary">click to browse</span>
                        <input type="file" name="attachments[]" class="d-none" multiple>
                    </label>
                    <small class="text-muted">JPG, PNG, PDF and documents. Max 10MB each.</small>
                </div>
                <div class="card-crm section-card"><div class="section-title">9. Assignment / Follow-up</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Assigned User</label><select name="assigned_user_id" class="form-select"><option value="">Unassigned</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('assigned_user_id', $lead->assigned_user_id)==$u->id)>{{ $u->name }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Team</label><select name="assigned_team_id" class="form-select"><option value="">None</option>@foreach($teams as $t)<option value="{{ $t->id }}" @selected(old('assigned_team_id', $lead->assigned_team_id)==$t->id)>{{ $t->name }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Co-assigned User</label><select name="co_assigned_user_id" class="form-select"><option value="">None</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('co_assigned_user_id', $lead->co_assigned_user_id)==$u->id)>{{ $u->name }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Next Follow-up</label><input type="datetime-local" name="next_followup_at" value="{{ old('next_followup_at', optional($lead->next_followup_at)->format('Y-m-d\TH:i')) }}" class="form-control"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 ai-panel">
                <div class="card-head mb-2"><h5>AI Lead Insights</h5><span class="badge-soft badge-score-mid">Beta</span></div>
                <div class="card-crm p-3 mb-3 text-center">
                    <div class="fw-bold mb-2">AI Lead Quality</div>
                    @php $sc = (int) ($lead->lead_score ?? 0); @endphp
                    <div class="gauge" style="--p: {{ $sc * 3.6 }}deg"><span>{{ $sc ?: '—' }}</span></div>
                    <div class="fw-bold text-success mb-2">{{ $sc >= 80 ? 'High Quality Lead' : ($sc ? 'Needs more detail' : 'Score after save') }}</div>
                    <div class="small text-start">
                        <div class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Complete contact information</div>
                        <div class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Specific location preference</div>
                        <div class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Budget range provided</div>
                        <div><i class="bi bi-check-circle-fill text-success"></i> Clear interest captured</div>
                    </div>
                </div>
                <div class="card-crm p-3 mb-3">
                    <h6 class="fw-bold"><i class="bi bi-shield-check text-success"></i> Duplicate Check</h6>
                    <p class="mb-2">No duplicates found until save. Matches use phone, WhatsApp, email and external ID.</p>
                    <a href="{{ route('crm.leads.duplicates') }}" class="btn btn-outline-soft btn-sm w-100">View Possible Matches</a>
                </div>
                <div class="card-crm p-3 mb-3">
                    <h6 class="fw-bold">Sentiment (Last Chat)</h6>
                    <div class="fs-5 mb-1">{{ $lead->sentiment === 'positive' ? '😊' : ($lead->sentiment === 'negative' ? '😞' : '😐') }} {{ ucfirst($lead->sentiment ?: 'Not analyzed yet') }}</div>
                    <div class="source-bar"><span style="width: {{ $lead->lead_score ?: 40 }}%; background:#16a34a"></span></div>
                    <small class="text-muted">Confidence Score {{ $lead->lead_score ?: 0 }}%</small>
                </div>
                <div class="card-crm p-3 mb-3">
                    <h6 class="fw-bold"><i class="bi bi-stars text-primary"></i> Recommended First Action</h6>
                    <p class="small mb-2">Complete contact details, then make first contact within SLA.</p>
                    <a href="{{ route('crm.ai.index') }}" class="btn btn-primary btn-sm w-100">Generate Message</a>
                </div>
                <div class="card-crm p-3">
                    <h6 class="fw-bold">AI Summary</h6>
                    <p class="small mb-0 text-muted">{{ $lead->exists ? ($lead->full_name.' is interested in '.($lead->interested_in ?: 'your offering').' with budget '.$lead->budget_label.'.') : 'Summary appears after the lead is saved.' }}</p>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.tag-dropdown').forEach(function (wrap) {
        const label = wrap.querySelector('.tag-dropdown-label');
        const boxes = wrap.querySelectorAll('input[type="checkbox"]');
        const sync = function () {
            const names = Array.from(boxes).filter(function (box) { return box.checked; }).map(function (box) {
                const name = box.closest('label').querySelector('.tag-name');
                return name ? name.textContent.trim() : '';
            }).filter(Boolean);
            label.textContent = names.length ? names.join(', ') : 'Select tags';
        };
        boxes.forEach(function (box) { box.addEventListener('change', sync); });
    });
});
</script>
@endpush
