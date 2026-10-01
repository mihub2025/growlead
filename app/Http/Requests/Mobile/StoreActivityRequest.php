<?php

namespace App\Http\Requests\Mobile;

use App\Enums\ActivityType;
use App\Enums\CallOutcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ActivityType::class)],
            'outcome' => ['nullable', Rule::enum(CallOutcome::class)],
            'status' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'subject' => ['nullable', 'string', 'max:190'],
            'schedule_follow_up' => ['sometimes', 'boolean'],
            'follow_up_at' => ['required_if:schedule_follow_up,true', 'nullable', 'date'],
            'follow_up_type' => ['nullable', 'string', 'max:40'],
            'follow_up_notes' => ['nullable', 'string', 'max:2000'],
            'reminder' => ['nullable', 'string', Rule::in(array_keys(config('mobile.reminder_offsets', [])))],
        ];
    }
}
