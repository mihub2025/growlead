<?php

namespace App\Events;

use App\Models\Campaign;
use App\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignLeadReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(public Lead $lead, public ?Campaign $campaign = null)
    {
    }
}
