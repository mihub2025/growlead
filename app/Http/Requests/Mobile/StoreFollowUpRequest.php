<?php

namespace App\Http\Requests\Mobile;

use App\Enums\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(TaskType::class)],
            'due_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reminder' => ['nullable', 'string', Rule::in(array_keys(config('mobile.reminder_offsets', [])))],
            'title' => ['nullable', 'string', 'max:190'],
        ];
    }
}
