<?php

namespace App\Http\Requests\Mobile;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:190'],
            'lead_id' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::enum(TaskType::class)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'due_at' => ['sometimes', 'required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'reminder' => ['nullable', 'string', Rule::in(array_keys(config('mobile.reminder_offsets', [])))],
        ];
    }
}
