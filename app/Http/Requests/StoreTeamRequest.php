<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('teams.manage') ?? false;
    }

    public function rules(): array
    {
        $orgId = $this->user()?->organization_id;

        return [
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string'],
            'manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('organization_id', $orgId)],
            'status' => ['nullable', 'string', 'max:40'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', Rule::exists('users', 'id')->where('organization_id', $orgId)],
        ];
    }
}
