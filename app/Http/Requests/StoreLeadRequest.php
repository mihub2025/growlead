<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('leads.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'alternate_phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:190'],
            'job_title' => ['nullable', 'string', 'max:190'],
            'source_id' => ['nullable', 'integer'],
            'campaign_id' => ['nullable', 'integer'],
            'pipeline_stage_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'string', 'max:40'],
            'assigned_user_id' => ['nullable', 'integer'],
            'assigned_team_id' => ['nullable', 'integer'],
            'co_assigned_user_id' => ['nullable', 'integer'],
            'interested_in' => ['nullable', 'string', 'max:190'],
            'category' => ['nullable', 'string', 'max:190'],
            'requirement' => ['nullable', 'string'],
            'quantity' => ['nullable', 'string', 'max:40'],
            'purpose' => ['nullable', 'string', 'max:80'],
            'decision_timeline' => ['nullable', 'string', 'max:80'],
            'min_budget' => ['nullable', 'numeric'],
            'max_budget' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:8'],
            'country' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'area' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'preferred_contact_method' => ['nullable', 'string', 'max:40'],
            'preferred_language' => ['nullable', 'string', 'max:40'],
            'preferred_contact_time' => ['nullable', 'string', 'max:40'],
            'medium' => ['nullable', 'string', 'max:80'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:120'],
            'email_opt_in' => ['nullable', 'boolean'],
            'sms_opt_in' => ['nullable', 'boolean'],
            'whatsapp_opt_in' => ['nullable', 'boolean'],
            'do_not_contact' => ['nullable', 'boolean'],
            'external_id' => ['nullable', 'string', 'max:120'],
            'lead_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'next_followup_at' => ['nullable', 'date'],
            'meta_created_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv'],
        ];
    }
}
