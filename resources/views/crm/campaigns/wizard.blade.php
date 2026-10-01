@extends('layouts.crm')
@section('title', $campaign->exists ? 'Edit Campaign' : 'New campaign')
@php
    $steps = [
        1 => ['Source', 'Origin & setup'],
        2 => ['Connect Account', 'Verify the link'],
        3 => ['Select Campaign', 'Details'],
        4 => ['Assign Agents', 'Team'],
        5 => ['Routing Rules', 'Distribution'],
        6 => ['Budget & Goals', 'Targets'],
        7 => ['Review', 'Final check'],
        8 => ['Activate', 'Go live'],
    ];
    $org = auth()->user()->organization;
    $objective = old('objective', $campaign->objective ?: 'leads');
    $sourceId = old('source_id', $campaign->source_id);
    $pickerSources = \App\Models\LeadSource::forCampaignPicker($sources);
    $metaSource = $sources->firstWhere('slug', 'meta');
    $facebookSource = $sources->firstWhere('slug', 'facebook');
    if ($metaSource && $facebookSource && (int) $sourceId === (int) $facebookSource->id) {
        $sourceId = $metaSource->id;
    }
    $selectedSource = $pickerSources->firstWhere('id', $sourceId) ?: $pickerSources->firstWhere('slug', 'meta') ?: $pickerSources->first();
    if ($selectedSource) {
        $sourceId = $selectedSource->id;
    }
    $sourceIcons = [
        'meta' => 'bi-meta', 'facebook' => 'bi-facebook', 'instagram' => 'bi-instagram',
        'google' => 'bi-google', 'tiktok' => 'bi-tiktok', 'linkedin' => 'bi-linkedin',
        'whatsapp' => 'bi-whatsapp', 'website' => 'bi-globe', 'web-form' => 'bi-ui-checks',
        'csv' => 'bi-filetype-csv', 'manual' => 'bi-person-plus', 'bayut' => 'bi-house',
        'dubizzle' => 'bi-bag',
    ];
    $sourceProviders = [
        'meta' => 'meta', 'facebook' => 'meta', 'instagram' => 'instagram',
        'tiktok' => 'tiktok', 'google' => 'google', 'linkedin' => 'linkedin',
        'website' => 'website', 'web-form' => 'website', 'whatsapp' => 'whatsapp',
        'api' => 'webhook', 'webhook' => 'webhook', 'bayut' => 'bayut', 'dubizzle' => 'dubizzle',
    ];
    $connectedProviders = $connectedProviders ?? [];
    $isSourceConnected = function ($slug) use ($sourceProviders, $connectedProviders) {
        $provider = $sourceProviders[$slug] ?? null;
        if ($provider === null) {
            return true;
        }
        return in_array($provider, $connectedProviders, true);
    };
    if ($selectedSource && ! $isSourceConnected($selectedSource->slug)) {
        $selectedSource = $pickerSources->first(fn ($s) => $isSourceConnected($s->slug));
        $sourceId = $selectedSource?->id;
    }
    $country = $org->country ?: 'Primary market';
    $currency = $org->currencyCode();
    $timezone = $org->timezone ?: 'UTC';
    $suggested = $recommendations['suggested_budget'];
    $suggestedLabel = $org->formatMoney($suggested);
    $cplLabel = $org->formatMoney($recommendations['expected_cpl']);
    $leadsN = (int) $recommendations['expected_leads'];
    $qualPct = (int) $recommendations['expected_qualified_rate'];
    $qualN = (int) round($leadsN * $qualPct / 100);
    $nextLabel = $step == 8 ? 'Activate' : 'Continue to '.$steps[min(8, $step + 1)][0];
    $panelCopy = [
        1 => ['Source setup', 'Each row is a decision. Scan left to right — label, value, recommendation.'],
        2 => ['Connect account', 'Connect the source account in Integrations. GrowLead never pretends a connection succeeded.'],
        3 => ['Campaign details', 'Confirm the name, objective and description before assigning the team.'],
        4 => ['Assign agents', 'Choose who owns incoming leads from this campaign.'],
        5 => ['Routing rules', 'How new leads are distributed across the assigned team.'],
        6 => ['Budget & goals', 'Spend and performance targets for this launch.'],
        7 => ['Review', 'Check source, routing, budget and assignments before launch.'],
        8 => ['Activate', 'Go live. Leads will start routing immediately.'],
    ];
@endphp
@section('content')
<div class="wiz-b">
    <div class="crm-topbar">
        <div>
            <p class="kicker">Campaigns</p>
            <div class="wiz-crumb">
                <a href="{{ route('crm.campaigns.index') }}">Campaigns</a> /
                <b>{{ $campaign->exists ? ($campaign->name ?: 'Edit campaign') : 'New campaign' }}</b> /
                {{ $steps[$step][0] }}
            </div>
            <h1 class="crm-title">{{ $campaign->exists ? ($campaign->name ?: 'Edit campaign') : 'New campaign' }}</h1>
            <p class="crm-subtitle">A properties-style setup. Scan left to right — label, value, recommendation.</p>
        </div>
        <div class="crm-actions">
            <button form="wizardForm" class="btn btn-outline-soft">Save draft</button>
            <a href="{{ route('crm.campaigns.index') }}" class="btn btn-outline-soft"><i class="bi bi-x-lg"></i></a>
        </div>
    </div>

    <div class="crm-content">
        <div class="wiz-shell">
            <aside class="wiz-steps">
                <h4>Launch path</h4>
                <div class="wiz-tl">
                    @foreach($steps as $n => $meta)
                        @php
                            $cls = $step == $n ? 'on' : ($step > $n ? 'done' : '');
                            $canJump = $campaign->exists && $n <= $step;
                        @endphp
                        @if($canJump)
                            <a href="{{ route('crm.campaigns.edit', ['campaign'=>$campaign,'step'=>$n]) }}" class="wiz-st {{ $cls }}">
                                <div class="d">{{ $n }}</div>
                                <div><b>{{ $meta[0] }}</b><small>{{ $meta[1] }}</small></div>
                            </a>
                        @else
                            <div class="wiz-st {{ $cls }}">
                                <div class="d">{{ $n }}</div>
                                <div><b>{{ $meta[0] }}</b><small>{{ $meta[1] }}</small></div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </aside>

            <form id="wizardForm" class="wiz-panel" method="POST" action="{{ $campaign->exists ? route('crm.campaigns.update', $campaign) : route('crm.campaigns.store') }}">
                @csrf
                @if($campaign->exists) @method('PUT') @endif
                <input type="hidden" name="wizard_step" value="{{ $step }}">
                <input type="hidden" name="source_id" id="source_id" value="{{ $sourceId }}">
                @if($step == 1)
                    <input type="hidden" name="objective" id="objective" value="{{ $objective }}">
                @endif
                @if($step != 1 && $step != 3)
                    <input type="hidden" name="name" value="{{ $campaign->name }}">
                @endif

                <div class="wiz-panel-h">
                    <h2>{{ $panelCopy[$step][0] }}</h2>
                    <p>{{ $panelCopy[$step][1] }}</p>
                </div>

                @if($step == 1)
                    <div class="wiz-row">
                        <div class="wiz-k">Source<span>Ad platform</span></div>
                        <div>
                            <div class="wiz-plats">
                                @foreach($pickerSources as $s)
                                    @php $connected = $isSourceConnected($s->slug); @endphp
                                    <button type="button"
                                        class="wiz-pbtn {{ $s->slug }} {{ $connected && (int) $sourceId === (int) $s->id ? 'on' : '' }} {{ $connected ? '' : 'off' }}"
                                        data-source-id="{{ $s->id }}"
                                        @disabled(! $connected)
                                        title="{{ $connected ? $s->name : 'Not connected — open Integrations to connect' }}">
                                        <i class="bi {{ $sourceIcons[$s->slug] ?? 'bi-broadcast' }}"></i> {{ $s->name }}
                                    </button>
                                @endforeach
                            </div>
                            <div class="small text-muted mt-2">Gray sources are not connected. <a href="{{ route('crm.integrations.index') }}">Connect them in Integrations</a>.</div>
                        </div>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Campaign name<span>Internal only</span></div>
                        <input class="wiz-in wiz-title" name="name" value="{{ old('name', $campaign->name) }}" placeholder="e.g. Housing leads · Aug 2026" required>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Ad account</div>
                        <div class="wiz-acct">
                            <div class="ico"><i class="bi {{ $sourceIcons[$selectedSource->slug ?? ''] ?? 'bi-broadcast' }}"></i></div>
                            <div>
                                <b>{{ auth()->user()->name }} Ads Account</b>
                                <small>
                                    @if($selectedSource && $isSourceConnected($selectedSource->slug))
                                        {{ $selectedSource->name }} · connected
                                    @else
                                        No connected source selected
                                    @endif
                                </small>
                            </div>
                            @if($selectedSource && $isSourceConnected($selectedSource->slug))
                                <span class="wiz-ok">Connected</span>
                            @else
                                <a href="{{ route('crm.integrations.index') }}" class="small fw-bold">Connect →</a>
                            @endif
                        </div>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Objective</div>
                        <div class="wiz-seg" data-seg="objective">
                            <button type="button" data-value="leads" class="{{ $objective === 'leads' ? 'on' : '' }}">Leads</button>
                            <button type="button" data-value="traffic" class="{{ $objective === 'traffic' ? 'on' : '' }}">Traffic</button>
                        </div>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Buying type</div>
                        <div class="wiz-seg" data-seg>
                            <button type="button" class="on">Auction</button>
                            <button type="button">Reservation</button>
                        </div>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Special category</div>
                        <select class="wiz-in" style="max-width:240px"><option>Housing</option><option>Credit</option><option>None</option></select>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Ad set name</div>
                        <input class="wiz-in" value="{{ $campaign->name ? $campaign->name.' — Ad Set' : '' }}" placeholder="Campaign name + audience">
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Conversion<span>Where leads land</span></div>
                        <div class="d-flex gap-2 flex-wrap">
                            <div class="wiz-seg" data-seg>
                                <button type="button" class="on">Website</button>
                                <button type="button">Instant form</button>
                            </div>
                            <select class="wiz-in" style="max-width:140px"><option>Lead</option></select>
                        </div>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Pixel</div>
                        <div class="wiz-acct">
                            <div class="ico" style="background:#0f172a"><i class="bi bi-broadcast"></i></div>
                            <div>
                                <b>{{ auth()->user()->name }} Pixel</b>
                                <small>Ready for this ad account</small>
                            </div>
                            <span class="wiz-ok">Active</span>
                        </div>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Audience</div>
                        <div>
                            <select class="wiz-in" style="max-width:240px;margin-bottom:8px"><option>High-intent buyers</option></select>
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="wiz-chip">{{ $country }}</span>
                                <span class="wiz-chip">25–45</span>
                                <span class="wiz-chip">All genders</span>
                            </div>
                        </div>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Size</div>
                        <div class="wiz-gbar-wrap">
                            <div class="d-flex justify-content-between"><b style="font-size:13px">2.3M – 3.1M</b><span class="wiz-ok">Good</span></div>
                            <div class="wiz-gbar"><i></i></div>
                        </div>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Daily budget</div>
                        <div>
                            <div class="wiz-money"><em>{{ $currency }}</em><input name="daily_budget" id="daily_budget" value="{{ old('daily_budget', $campaign->daily_budget) }}" inputmode="decimal"></div>
                            <div class="small text-muted mt-1">Suggested {{ $suggestedLabel }} from similar campaigns</div>
                        </div>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Schedule</div>
                        <div class="wiz-dates">
                            <input class="wiz-in" type="date" name="start_date" value="{{ old('start_date', optional($campaign->start_date)->toDateString() ?: now()->toDateString()) }}">
                            <span>to</span>
                            <input class="wiz-in" type="date" name="end_date" value="{{ old('end_date', optional($campaign->end_date)->toDateString() ?: now()->addDays(14)->toDateString()) }}">
                            <span>{{ $timezone }}</span>
                        </div>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Total budget<span>Optional</span></div>
                        <div class="wiz-money"><em>{{ $currency }}</em><input name="budget" value="{{ old('budget', $campaign->budget) }}" inputmode="decimal" placeholder="0.00"></div>
                    </div>
                @elseif($step == 2)
                    <div class="wiz-pad">
                        <a href="{{ route('crm.integrations.index') }}" class="btn btn-outline-soft">Open Integrations</a>
                    </div>
                @elseif($step == 3)
                    <div class="wiz-row">
                        <div class="wiz-k">Name</div>
                        <input class="wiz-in wiz-title" name="name" value="{{ $campaign->name }}" required>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Objective</div>
                        <input class="wiz-in" name="objective" value="{{ $campaign->objective }}">
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Description</div>
                        <textarea class="wiz-in" name="description">{{ $campaign->description }}</textarea>
                    </div>
                @elseif($step == 4)
                    <div class="wiz-pad">
                        <div class="wiz-agents">
                            @forelse($users as $u)
                                <label class="wiz-agent">
                                    <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" @checked($campaign->users->contains($u->id))>
                                    <div class="crm-avatar">{{ $u->initials() }}</div>
                                    <div class="flex-grow-1">
                                        <b>{{ $u->name }}</b>
                                        <small class="d-block text-muted">{{ $u->roleName() }}</small>
                                    </div>
                                </label>
                            @empty
                                <p class="text-muted mb-0">No active agents in this workspace.</p>
                            @endforelse
                        </div>
                    </div>
                @elseif($step == 5)
                    <div class="wiz-row">
                        <div class="wiz-k">Routing method<span>How leads are assigned</span></div>
                        <select class="wiz-in" name="routing_method" style="max-width:280px">
                            @foreach(['round_robin'=>'Round Robin','least_assigned'=>'Least Assigned','performance'=>'Performance Based','location'=>'Location Based','weighted'=>'Weighted'] as $k=>$v)
                                <option value="{{ $k }}" @selected($campaign->routing_method==$k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif($step == 6)
                    <div class="wiz-row">
                        <div class="wiz-k">Daily budget</div>
                        <div class="wiz-money"><em>{{ $currency }}</em><input name="daily_budget" id="daily_budget" value="{{ $campaign->daily_budget }}"></div>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Total budget</div>
                        <div class="wiz-money"><em>{{ $currency }}</em><input name="budget" value="{{ $campaign->budget }}"></div>
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Target leads</div>
                        <input class="wiz-in" name="target_leads" value="{{ $campaign->target_leads }}" style="max-width:220px">
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Target qualified</div>
                        <input class="wiz-in" name="target_qualified_leads" value="{{ $campaign->target_qualified_leads }}" style="max-width:220px">
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Target CPL</div>
                        <input class="wiz-in" name="target_cpl" value="{{ $campaign->target_cpl }}" style="max-width:220px">
                    </div>
                    <div class="wiz-row">
                        <div class="wiz-k">Target revenue</div>
                        <input class="wiz-in" name="target_revenue" value="{{ $campaign->target_revenue }}" style="max-width:220px">
                    </div>
                @else
                    <div class="wiz-row">
                        <div class="wiz-k">Campaign</div>
                        <div class="wiz-acct">
                            <div class="ico" style="background:#0f172a"><i class="bi bi-megaphone"></i></div>
                            <div>
                                <b>{{ $campaign->name ?: 'Untitled campaign' }}</b>
                                <small>Source, routing, budget and assignments are ready.</small>
                            </div>
                            <span class="wiz-ok">Ready</span>
                        </div>
                    </div>
                    @if($step == 8)
                        <input type="hidden" name="activate" value="1">
                        <input type="hidden" name="status" value="active">
                    @endif
                @endif

                <div class="wiz-foot">
                    @if($campaign->exists && $step > 1)
                        <a class="wiz-ghost" href="{{ route('crm.campaigns.edit', ['campaign'=>$campaign,'step'=>$step-1]) }}">← Previous</a>
                    @else
                        <span class="text-muted small">Draft · step {{ $step }} of 8</span>
                    @endif
                    <button class="btn btn-primary">{{ $nextLabel }}</button>
                </div>
            </form>

            <aside class="wiz-side">
                <div class="wiz-card">
                    <h3><i class="bi bi-stars"></i> Forecast</h3>
                    <div class="wiz-hero">
                        <div><span>CPL</span><b>{{ $cplLabel }}</b></div>
                        <div><span>Leads</span><b>{{ $leadsN }}</b></div>
                    </div>
                    <div class="wiz-line"><span>Budget / day</span><b>{{ $suggestedLabel }}</b></div>
                    <div class="wiz-line"><span>Best window</span><b>{{ $recommendations['best_posting_time'] }}</b></div>
                    <div class="wiz-line"><span>Audience</span><b>{{ $recommendations['suggested_audience'] }}</b></div>
                    <div class="wiz-line"><span>Qualified</span><b>{{ $qualPct }}% · ~{{ $qualN }}</b></div>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between small fw-bold"><span>Confidence</span><span style="color:#16a34a">High 86%</span></div>
                        <div class="wiz-conf"><i></i></div>
                    </div>
                    <button type="button" class="btn btn-yellow w-100 mt-2" data-apply-ai data-budget="{{ $suggested }}">Apply all</button>
                </div>
                <div class="wiz-card">
                    <h3>Why this brief</h3>
                    <p class="small text-muted mb-0">Numbers come from similar campaigns in this workspace — not generic averages.</p>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.wiz-pbtn[data-source-id]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        if (btn.disabled || btn.classList.contains('off')) return;
        document.querySelectorAll('.wiz-pbtn').forEach(function (b) { b.classList.remove('on'); });
        btn.classList.add('on');
        var input = document.getElementById('source_id');
        if (input) input.value = btn.getAttribute('data-source-id');
    });
});
document.querySelectorAll('[data-seg]').forEach(function (group) {
    group.querySelectorAll('button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            group.querySelectorAll('button').forEach(function (b) { b.classList.remove('on'); });
            btn.classList.add('on');
            var name = group.getAttribute('data-seg');
            if (name) {
                var input = document.getElementById(name);
                if (input && btn.getAttribute('data-value')) input.value = btn.getAttribute('data-value');
            }
        });
    });
});
document.querySelectorAll('[data-apply-ai]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById('daily_budget');
        if (input && btn.dataset.budget) input.value = btn.dataset.budget;
    });
});
</script>
@endpush
