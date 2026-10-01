<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('automations.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'trigger' => ['required', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:40'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
        ];
    }
}
