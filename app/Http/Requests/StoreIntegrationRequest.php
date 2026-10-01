<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('integrations.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:190'],
            'status' => ['nullable', 'string', 'max:40'],
            'credentials' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
