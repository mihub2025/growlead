<?php

namespace App\Services\AI;

use App\Models\Lead;

class RecommendedActionService
{
    public function recommend(Lead $lead): array
    {
        if (! $lead->first_response_at) {
            return ['action' => 'Call', 'label' => 'Make first contact', 'channel' => 'call'];
        }
        if ($lead->lead_score >= 80) {
            return ['action' => 'Meeting', 'label' => 'Schedule a meeting or demo', 'channel' => 'meeting'];
        }
        if ($lead->interested_in) {
            return ['action' => 'WhatsApp', 'label' => 'Send matching options', 'channel' => 'whatsapp'];
        }

        return ['action' => 'Follow-up', 'label' => 'Send a follow-up message', 'channel' => 'email'];
    }
}
