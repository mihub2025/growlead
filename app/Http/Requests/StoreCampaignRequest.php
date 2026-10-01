<?php

namespace App\Http\Requests;

use App\Models\LeadSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('campaigns.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'source_id' => ['nullable', 'integer'],
            'objective' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'budget_type' => ['nullable', 'string', 'max:40'],
            'budget' => ['nullable', 'numeric'],
            'daily_budget' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:8'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:40'],
            'routing_method' => ['nullable', 'string', 'max:40'],
            'target_leads' => ['nullable', 'integer'],
            'target_qualified_leads' => ['nullable', 'integer'],
            'target_cpl' => ['nullable', 'numeric'],
            'target_revenue' => ['nullable', 'numeric'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
            'wizard_step' => ['nullable', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $sourceId = $this->input('source_id');
            if (! $sourceId) {
                return;
            }

            $orgId = $this->user()?->organization_id;
            $source = LeadSource::query()
                ->where('id', $sourceId)
                ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
                ->first();

            if ($source?->isLeadOrigin()) {
                $validator->errors()->add('source_id', 'CSV, Manual, Referral, and Other are for adding leads, not for campaigns.');
            }
        });
    }
}
