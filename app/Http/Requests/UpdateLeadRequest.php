<?php

namespace App\Http\Requests;

class UpdateLeadRequest extends StoreLeadRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('leads.edit') ?? false;
    }
}
