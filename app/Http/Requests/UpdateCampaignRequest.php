<?php

namespace App\Http\Requests;

class UpdateCampaignRequest extends StoreCampaignRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('campaigns.edit') ?? false;
    }
}
