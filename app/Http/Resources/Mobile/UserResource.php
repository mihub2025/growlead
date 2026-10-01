<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'initials' => $this->initials(),
            'role' => $this->roleName(),
            'role_slug' => $this->roleSlug(),
            'status' => $this->status,
            'organization' => $this->organization ? [
                'name' => $this->organization->name,
                'timezone' => $this->organization->timezone,
                'currency' => $this->organization->currencyCode(),
            ] : null,
        ];
    }
}
