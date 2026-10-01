<?php

namespace App\Services\AI;

use App\Models\Lead;

class DuplicateAnalysisService
{
    public function risk(Lead $lead): array
    {
        $count = $lead->duplicates()->where('status', 'pending')->count();

        return [
            'level' => $count ? ($count > 2 ? 'high' : 'medium') : 'low',
            'count' => $count,
        ];
    }
}
