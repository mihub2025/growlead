<?php

namespace App\Services\AI;

use App\Models\Lead;

class IntentDetectionService
{
    public function detect(Lead $lead): string
    {
        return $lead->scoreLabel();
    }
}
