<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['status', 'labels', 'campaigns', 'agents', 'task_types', 'task_status', 'sources'] as $key) {
            $value = $this->input($key);
            if ($value === null || $value === '') {
                continue;
            }
            $merge[$key] = array_values(array_filter(is_array($value) ? $value : explode(',', (string) $value), fn ($item) => $item !== '' && $item !== null));
        }

        if (! $this->filled('campaigns') && $this->filled('campaign_id')) {
            $merge['campaigns'] = [(int) $this->input('campaign_id')];
        }
        if (! $this->filled('agents') && $this->filled('assigned_user_id')) {
            $merge['agents'] = [(int) $this->input('assigned_user_id')];
        }
        if (! $this->filled('sources') && $this->filled('source_id')) {
            $merge['sources'] = [(int) $this->input('source_id')];
        }
        if (! $this->filled('labels') && $this->filled('tags')) {
            $tags = $this->input('tags');
            $merge['labels'] = array_values(array_filter(is_array($tags) ? $tags : explode(',', (string) $tags)));
        }
        if (! $this->filled('creation_date') && ! $this->filled('creation_from') && $this->filled('from')) {
            $merge['creation_date'] = 'custom';
            $merge['creation_from'] = $this->input('from');
        }
        if (! $this->filled('creation_to') && $this->filled('to')) {
            $merge['creation_date'] = $merge['creation_date'] ?? 'custom';
            $merge['creation_to'] = $this->input('to');
        }

        foreach (['include_unassigned', 'campaign_created_by_agent'] as $boolKey) {
            if ($this->has($boolKey)) {
                $merge[$boolKey] = filter_var($this->input($boolKey), FILTER_VALIDATE_BOOLEAN);
            }
        }

        if ($merge) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'tab' => ['nullable', 'string', 'max:40'],
            'search' => ['nullable', 'string', 'max:190'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'in:created_at,lead_score,last_activity_at,first_name,updated_at'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'applied' => ['nullable'],

            'status' => ['nullable', 'array'],
            'status.*' => ['string', 'max:80'],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['integer'],
            'campaigns' => ['nullable', 'array'],
            'campaigns.*' => ['integer'],
            'campaign_scope' => ['nullable', 'string', 'in:active,archived,paused'],
            'campaign_created_by_agent' => ['nullable', 'boolean'],
            'agents' => ['nullable', 'array'],
            'agents.*' => ['integer'],
            'include_unassigned' => ['nullable', 'boolean'],
            'agent_scope' => ['nullable', 'string', 'in:active,paused,archived'],

            'last_updated_operator' => ['nullable', 'string', 'in:more,less'],
            'last_updated_value' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'last_updated_unit' => ['nullable', 'string', 'in:minutes,hours,days'],
            'last_updated_applied' => ['nullable'],

            'last_assigned_operator' => ['nullable', 'string', 'in:more,less'],
            'last_assigned_value' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'last_assigned_unit' => ['nullable', 'string', 'in:minutes,hours,days'],
            'last_assigned_applied' => ['nullable'],

            'creation_date' => ['nullable', 'string', 'in:today,yesterday,last_7_days,this_week,this_month,last_month,this_quarter,last_quarter,custom'],
            'creation_from' => ['nullable', 'date'],
            'creation_to' => ['nullable', 'date'],

            'meta_creation_date' => ['nullable', 'string', 'in:today,yesterday,last_7_days,this_week,this_month,last_month,this_quarter,last_quarter,custom'],
            'meta_creation_from' => ['nullable', 'date'],
            'meta_creation_to' => ['nullable', 'date'],

            'deal_value_from' => ['nullable', 'numeric', 'min:0'],
            'deal_value_to' => ['nullable', 'numeric', 'min:0'],
            'deal_value_applied' => ['nullable'],

            'task_types' => ['nullable', 'array'],
            'task_types.*' => ['string', 'max:80'],
            'task_status' => ['nullable', 'array'],
            'task_status.*' => ['string', 'in:due,overdue,no_tasks,no_tasks_set'],

            'calls_operator' => ['nullable', 'string', 'in:more,less,equal'],
            'calls_value' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'calls_applied' => ['nullable'],

            'sources' => ['nullable', 'array'],
            'sources.*' => ['integer'],
            'source_medium' => ['nullable', 'string', 'max:80'],
            'source_utm_source' => ['nullable', 'string', 'max:120'],
            'source_utm_medium' => ['nullable', 'string', 'max:120'],

            'times_assigned_operator' => ['nullable', 'string', 'in:more,less,equal'],
            'times_assigned_value' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'times_assigned_applied' => ['nullable'],

            'pipeline_stage_id' => ['nullable', 'integer'],
            'assigned_team_id' => ['nullable', 'integer'],
            'priority' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:80'],
            'interested_in' => ['nullable', 'string', 'max:190'],
            'min_score' => ['nullable', 'numeric'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'source_id' => ['nullable', 'integer'],
            'campaign_id' => ['nullable', 'integer'],
            'assigned_user_id' => ['nullable', 'integer'],
            'tags' => ['nullable'],
        ];
    }
}
