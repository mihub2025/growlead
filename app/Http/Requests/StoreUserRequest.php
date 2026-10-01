<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190'],
            'role_id' => ['required', 'integer'],
            'team_id' => ['nullable', 'integer'],
            'campaign_ids' => ['nullable', 'array'],
            'message' => ['nullable', 'string', 'max:250'],
            'permissions' => ['nullable', 'array'],
        ];
    }
}
