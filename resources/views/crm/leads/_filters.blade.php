@php
    $fs = $filterState['values'] ?? [];
    $fc = $filterState['counts'] ?? [];
    $hasActive = $filterState['hasActive'] ?? false;
    $selectedStatus = array_map('strval', (array) ($fs['status'] ?? request('status', [])));
    $selectedLabels = array_map('intval', (array) ($fs['labels'] ?? request('labels', [])));
    $selectedCampaigns = array_map('intval', (array) ($fs['campaigns'] ?? request('campaigns', [])));
    $selectedAgents = array_map('intval', (array) ($fs['agents'] ?? request('agents', [])));
    $selectedTaskTypes = array_map('strval', (array) ($fs['task_types'] ?? request('task_types', [])));
    $selectedTaskStatus = array_map('strval', (array) ($fs['task_status'] ?? request('task_status', [])));
    $selectedSources = array_map('intval', (array) ($fs['sources'] ?? request('sources', [])));
    $datePresets = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 days',
        'this_week' => 'This week',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_quarter' => 'This quarter',
        'last_quarter' => 'Last quarter',
    ];
    $clearQuery = array_filter([
        'tab' => ($tab ?? 'all') !== 'all' ? $tab : null,
        'search' => request('search'),
        'sort' => request('sort'),
        'dir' => request('dir'),
        'per_page' => request('per_page'),
    ]);
    $clearUrl = route('crm.leads.index', $clearQuery);
    $campaignScope = $fs['campaign_scope'] ?? request('campaign_scope', 'active');
    $agentScope = $fs['agent_scope'] ?? request('agent_scope', 'active');
@endphp

<div class="lf-wrap mb-3" id="leadFilters">
    @if($hasActive)
        <div class="lf-applied">
            <span class="lf-applied-msg"><i class="bi bi-check-circle-fill"></i> Changes applied.</span>
            <a class="lf-refresh" href="{{ request()->fullUrl() }}"><i class="bi bi-arrow-clockwise"></i> Refresh</a>
        </div>
    @endif

    <div class="lf-toolbar">
        <div class="lf-bar" data-lead-filters>
            {{-- Status --}}
            <div class="lf-filter" data-filter="status" data-type="multi">
                <button type="button" class="lf-pill {{ ($fc['status'] ?? 0) ? 'is-active' : '' }}">
                    Status @if($fc['status'] ?? 0)<span class="lf-badge">{{ $fc['status'] }}</span>@endif
                    <i class="bi bi-chevron-down lf-caret"></i>
                </button>
                <div class="lf-drop">
                    <div class="lf-drop-title">Status</div>
                    <div class="lf-search-wrap"><input type="search" class="form-control lf-search" placeholder="Search for status"></div>
                    <div class="lf-options">
                        <label class="lf-option"><input type="checkbox" data-select-all> <span>Select All</span></label>
                        @foreach($filterOptions['statuses'] as $status)
                            <label class="lf-option" data-option data-search="{{ strtolower($status['name'].' '.$status['slug']) }}">
                                <input type="checkbox" name="status[]" value="{{ $status['slug'] }}" @checked(in_array($status['slug'], $selectedStatus, true) || in_array($status['name'], $selectedStatus, true))>
                                <span>{{ $status['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="lf-drop-foot"><button type="button" class="btn btn-yellow w-100 lf-apply">Apply</button></div>
                </div>
            </div>

            {{-- Label --}}
            <div class="lf-filter" data-filter="labels" data-type="multi">
                <button type="button" class="lf-pill {{ ($fc['labels'] ?? 0) ? 'is-active' : '' }}">
                    Label @if($fc['labels'] ?? 0)<span class="lf-badge">{{ $fc['labels'] }}</span>@endif
                    <i class="bi bi-chevron-down lf-caret"></i>
                </button>
                <div class="lf-drop">
                    <div class="lf-drop-title">Label</div>
                    <div class="lf-search-wrap"><input type="search" class="form-control lf-search" placeholder="Search for label"></div>
                    <div class="lf-options">
                        <label class="lf-option"><input type="checkbox" data-select-all> <span>Select All</span></label>
                        @foreach($filterOptions['tags'] as $tag)
                            <label class="lf-option" data-option data-search="{{ strtolower($tag->name) }}">
                                <input type="checkbox" name="labels[]" value="{{ $tag->id }}" @checked(in_array((int) $tag->id, $selectedLabels, true))>
                                <span class="lf-label-chip" style="background: {{ $tag->color }}22; color: {{ $tag->color }}; border-color: {{ $tag->color }}55;">{{ $tag->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="lf-drop-foot"><button type="button" class="btn btn-yellow w-100 lf-apply">Apply</button></div>
                </div>
            </div>

            {{-- Campaign --}}
            <div class="lf-filter" data-filter="campaigns" data-type="multi" data-current-tab="{{ $campaignScope }}">
                <button type="button" class="lf-pill {{ ($fc['campaigns'] ?? 0) ? 'is-active' : '' }}">
                    Campaign @if($fc['campaigns'] ?? 0)<span class="lf-badge">{{ $fc['campaigns'] }}</span>@endif
                    <i class="bi bi-chevron-down lf-caret"></i>
                </button>
                <div class="lf-drop lf-drop-wide">
                    <div class="lf-drop-title">Campaign</div>
                    <div class="lf-seg" data-tabs>
                        @foreach(['active'=>'Active','archived'=>'Archived','paused'=>'On pause'] as $key=>$label)
                            <button type="button" data-tab="{{ $key }}" class="{{ $campaignScope === $key ? 'active' : '' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <input type="hidden" name="campaign_scope" value="{{ $campaignScope }}" data-scope-input>
                    <div class="lf-search-wrap"><input type="search" class="form-control lf-search" placeholder="Search for campaign"></div>
                    <div class="lf-options">
                        <label class="lf-option">
                            <input type="checkbox" name="campaign_created_by_agent" value="1" data-special @checked(!empty($fs['campaign_created_by_agent']) || request()->boolean('campaign_created_by_agent'))>
                            <span>Created by agent</span>
                        </label>
                        <label class="lf-option"><input type="checkbox" data-select-all> <span>Select All</span></label>
                        @foreach($filterOptions['campaigns'] as $campaign)
                            @php $createdByAgent = $campaign->creator?->hasRole('agent'); @endphp
                            <label class="lf-option {{ $campaign->statusGroup() !== $campaignScope ? 'd-none' : '' }}" data-option data-scope="{{ $campaign->statusGroup() }}" data-search="{{ strtolower($campaign->name) }}" data-created-by-agent="{{ $createdByAgent ? '1' : '0' }}">
                                <input type="checkbox" name="campaigns[]" value="{{ $campaign->id }}" @checked(in_array((int) $campaign->id, $selectedCampaigns, true))>
                                <span>{{ $campaign->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="lf-drop-foot"><button type="button" class="btn btn-yellow w-100 lf-apply">Apply</button></div>
                </div>
            </div>

            @unless(!empty($isScopedAgent))
            {{-- Agent --}}
            <div class="lf-filter" data-filter="agents" data-type="multi" data-current-tab="{{ $agentScope }}">
                <button type="button" class="lf-pill {{ ($fc['agents'] ?? 0) ? 'is-active' : '' }}">
                    Agent @if($fc['agents'] ?? 0)<span class="lf-badge">{{ $fc['agents'] }}</span>@endif
                    <i class="bi bi-chevron-down lf-caret"></i>
                </button>
                <div class="lf-drop lf-drop-wide">
                    <div class="lf-drop-title">Agent</div>
                    <div class="lf-seg" data-tabs>
                        @foreach(['active'=>'Active','paused'=>'Paused','archived'=>'Archived'] as $key=>$label)
                            <button type="button" data-tab="{{ $key }}" class="{{ $agentScope === $key ? 'active' : '' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <input type="hidden" name="agent_scope" value="{{ $agentScope }}" data-scope-input>
                    <div class="lf-search-wrap"><input type="search" class="form-control lf-search" placeholder="Search for agent"></div>
                    <div class="lf-options">
                        <label class="lf-option">
                            <input type="checkbox" name="include_unassigned" value="1" data-special @checked(!empty($fs['include_unassigned']) || request()->boolean('include_unassigned'))>
                            <span>Unassigned</span>
                        </label>
                        <label class="lf-option"><input type="checkbox" data-select-all> <span>Select All</span></label>
                        @foreach($filterOptions['agents'] as $agent)
                            <label class="lf-option lf-agent {{ $agent->status !== $agentScope ? 'd-none' : '' }}" data-option data-scope="{{ $agent->status }}" data-search="{{ strtolower($agent->name.' '.$agent->email.' '.$agent->roleName()) }}">
                                <input type="checkbox" name="agents[]" value="{{ $agent->id }}" @checked(in_array((int) $agent->id, $selectedAgents, true))>
                                <span class="avatar lf-agent-avatar">
                                    @if($agent->avatar)<img src="{{ $agent->avatar }}" alt="">@else{{ $agent->initials() }}@endif
                                </span>
                                <span class="lf-agent-meta">
                                    <strong>{{ $agent->name }}</strong>
                                    <span class="lf-agent-sub">{{ $agent->email }}</span>
                                </span>
                                <span class="lf-agent-role">{{ $agent->roleName() }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="lf-drop-foot"><button type="button" class="btn btn-yellow w-100 lf-apply">Apply</button></div>
                </div>
            </div>

            {{-- Last Updated --}}
            @include('crm.leads._relative-filter', [
                'key' => 'last_updated',
                'label' => 'Last Updated',
                'active' => $fc['last_updated'] ?? 0,
                'operator' => $fs['last_updated_operator'] ?? request('last_updated_operator', 'more'),
                'value' => $fs['last_updated_value'] ?? request('last_updated_value', 0),
                'unit' => $fs['last_updated_unit'] ?? request('last_updated_unit', 'hours'),
                'applied' => !empty($fs['last_updated_operator']) || request()->filled('last_updated_operator') || request()->filled('last_updated_applied'),
                'showUnit' => true,
            ])

            {{-- Last Assigned --}}
            @include('crm.leads._relative-filter', [
                'key' => 'last_assigned',
                'label' => 'Last Assigned',
                'active' => $fc['last_assigned'] ?? 0,
                'operator' => $fs['last_assigned_operator'] ?? request('last_assigned_operator', 'more'),
                'value' => $fs['last_assigned_value'] ?? request('last_assigned_value', 0),
                'unit' => $fs['last_assigned_unit'] ?? request('last_assigned_unit', 'hours'),
                'applied' => !empty($fs['last_assigned_operator']) || request()->filled('last_assigned_operator') || request()->filled('last_assigned_applied'),
                'showUnit' => true,
            ])
            @endunless

            {{-- Creation Date --}}
            @include('crm.leads._date-filter', [
                'key' => 'creation',
                'param' => 'creation_date',
                'label' => 'Creation Date',
                'active' => $fc['creation_date'] ?? 0,
                'selected' => $fs['creation_date'] ?? request('creation_date'),
                'from' => $fs['creation_from'] ?? request('creation_from'),
                'to' => $fs['creation_to'] ?? request('creation_to'),
                'presets' => $datePresets,
            ])

            @unless(!empty($isScopedAgent))
            {{-- Meta Creation Date --}}
            @include('crm.leads._date-filter', [
                'key' => 'meta_creation',
                'param' => 'meta_creation_date',
                'label' => 'Meta Creation Date',
                'active' => $fc['meta_creation_date'] ?? 0,
                'selected' => $fs['meta_creation_date'] ?? request('meta_creation_date'),
                'from' => $fs['meta_creation_from'] ?? request('meta_creation_from'),
                'to' => $fs['meta_creation_to'] ?? request('meta_creation_to'),
                'presets' => $datePresets,
            ])

            {{-- Deal Value --}}
            <div class="lf-filter" data-filter="deal_value" data-type="range" data-applied="{{ ($fc['deal_value'] ?? 0) ? '1' : '0' }}">
                <button type="button" class="lf-pill {{ ($fc['deal_value'] ?? 0) ? 'is-active' : '' }}">
                    Deal Value @if($fc['deal_value'] ?? 0)<span class="lf-dot"></span>@endif
                    <i class="bi bi-chevron-down lf-caret"></i>
                </button>
                <div class="lf-drop">
                    <div class="lf-drop-title">Deal Value</div>
                    <div class="lf-range">
                        <div>
                            <label class="form-label">From</label>
                            <input type="number" min="0" step="1" class="form-control" name="deal_value_from" value="{{ $fs['deal_value_from'] ?? request('deal_value_from', 0) }}">
                        </div>
                        <div>
                            <label class="form-label">To</label>
                            <input type="number" min="0" step="1" class="form-control" name="deal_value_to" value="{{ $fs['deal_value_to'] ?? request('deal_value_to', 1000) }}">
                        </div>
                    </div>
                    <input type="hidden" name="deal_value_applied" value="{{ ($fc['deal_value'] ?? 0) ? '1' : '' }}" data-applied-flag>
                    <div class="lf-drop-foot"><button type="button" class="btn btn-yellow w-100 lf-apply">Apply</button></div>
                </div>
            </div>

            {{-- Task Type --}}
            <div class="lf-filter" data-filter="task_types" data-type="multi">
                <button type="button" class="lf-pill {{ ($fc['task_types'] ?? 0) ? 'is-active' : '' }}">
                    Task Type @if($fc['task_types'] ?? 0)<span class="lf-badge">{{ $fc['task_types'] }}</span>@endif
                    <i class="bi bi-chevron-down lf-caret"></i>
                </button>
                <div class="lf-drop">
                    <div class="lf-drop-title">Task Type</div>
                    <div class="lf-options">
                        <label class="lf-option"><input type="checkbox" data-select-all> <span>Select All</span></label>
                        @foreach($filterOptions['taskTypes'] as $type)
                            <label class="lf-option" data-option data-search="{{ strtolower($type['name'].' '.$type['slug']) }}">
                                <input type="checkbox" name="task_types[]" value="{{ $type['slug'] }}" @checked(in_array($type['slug'], $selectedTaskTypes, true))>
                                <span>{{ $type['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="lf-drop-foot"><button type="button" class="btn btn-yellow w-100 lf-apply">Apply</button></div>
                </div>
            </div>

            {{-- Task Status --}}
            <div class="lf-filter" data-filter="task_status" data-type="multi">
                <button type="button" class="lf-pill {{ ($fc['task_status'] ?? 0) ? 'is-active' : '' }}">
                    Task Status @if($fc['task_status'] ?? 0)<span class="lf-badge">{{ $fc['task_status'] }}</span>@endif
                    <i class="bi bi-chevron-down lf-caret"></i>
                </button>
                <div class="lf-drop">
                    <div class="lf-drop-title">Task Status</div>
                    <div class="lf-options">
                        <label class="lf-option"><input type="checkbox" data-select-all> <span>Select All</span></label>
                        @foreach(['due'=>'Due','overdue'=>'Overdue','no_tasks_set'=>'No tasks set'] as $value=>$label)
                            <label class="lf-option" data-option>
                                <input type="checkbox" name="task_status[]" value="{{ $value }}" @checked(in_array($value, $selectedTaskStatus, true) || ($value === 'no_tasks_set' && in_array('no_tasks', $selectedTaskStatus, true)))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="lf-drop-foot"><button type="button" class="btn btn-yellow w-100 lf-apply">Apply</button></div>
                </div>
            </div>

            {{-- Calls Made --}}
            @include('crm.leads._relative-filter', [
                'key' => 'calls',
                'label' => 'Calls Made',
                'active' => $fc['calls'] ?? 0,
                'operator' => $fs['calls_operator'] ?? request('calls_operator', 'more'),
                'value' => $fs['calls_value'] ?? request('calls_value', 0),
                'unit' => null,
                'applied' => !empty($fs['calls_operator']) || request()->filled('calls_operator') || request()->filled('calls_applied'),
                'showUnit' => false,
                'equal' => true,
            ])
            @endunless

            {{-- Lead Source --}}
            <div class="lf-filter" data-filter="sources" data-type="multi">
                <button type="button" class="lf-pill {{ ($fc['sources'] ?? 0) ? 'is-active' : '' }}">
                    Lead Source @if($fc['sources'] ?? 0)<span class="lf-badge">{{ $fc['sources'] }}</span>@endif
                    <i class="bi bi-chevron-down lf-caret"></i>
                </button>
                <div class="lf-drop">
                    <div class="lf-drop-title">Lead Source</div>
                    <div class="lf-search-wrap"><input type="search" class="form-control lf-search" placeholder="Search for source"></div>
                    <div class="lf-options">
                        <label class="lf-option"><input type="checkbox" data-select-all> <span>Select All</span></label>
                        @foreach($filterOptions['sources'] as $source)
                            <label class="lf-option" data-option data-search="{{ strtolower($source->name.' '.$source->slug) }}">
                                <input type="checkbox" name="sources[]" value="{{ $source->id }}" @checked(in_array((int) $source->id, $selectedSources, true))>
                                <span>{{ $source->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="lf-advanced {{ filled($fs['source_medium'] ?? request('source_medium')) || filled($fs['source_utm_source'] ?? request('source_utm_source')) || filled($fs['source_utm_medium'] ?? request('source_utm_medium')) ? '' : 'd-none' }}" data-advanced>
                        <label class="form-label">Medium</label>
                        <input class="form-control mb-2" name="source_medium" value="{{ $fs['source_medium'] ?? request('source_medium') }}" placeholder="Medium">
                        <label class="form-label">UTM Source</label>
                        <input class="form-control mb-2" name="source_utm_source" value="{{ $fs['source_utm_source'] ?? request('source_utm_source') }}" placeholder="utm_source">
                        <label class="form-label">UTM Medium</label>
                        <input class="form-control mb-2" name="source_utm_medium" value="{{ $fs['source_utm_medium'] ?? request('source_utm_medium') }}" placeholder="utm_medium">
                    </div>
                    <div class="lf-drop-foot lf-drop-foot-split">
                        <button type="button" class="btn btn-outline-soft lf-advanced-toggle">Advanced</button>
                        <button type="button" class="btn btn-yellow lf-apply">Apply</button>
                    </div>
                </div>
            </div>

            @unless(!empty($isScopedAgent))
            {{-- Times Assigned --}}
            @include('crm.leads._relative-filter', [
                'key' => 'times_assigned',
                'label' => 'Times Assigned',
                'active' => $fc['times_assigned'] ?? 0,
                'operator' => $fs['times_assigned_operator'] ?? request('times_assigned_operator', 'more'),
                'value' => $fs['times_assigned_value'] ?? request('times_assigned_value', 0),
                'unit' => null,
                'applied' => !empty($fs['times_assigned_operator']) || request()->filled('times_assigned_operator') || request()->filled('times_assigned_applied'),
                'showUnit' => false,
                'equal' => true,
            ])
            @endunless
        </div>

        @if($hasActive)
            <a class="lf-clear-all" href="{{ $clearUrl }}">Clear All</a>
        @endif
    </div>

    <div class="lf-total">
        {{ number_format($leads->total()) }}
        {{ $hasActive ? 'matching leads' : 'Total leads' }}
    </div>
</div>
