<?php

namespace App\Services\AI;

use App\Models\Campaign;

class CampaignRecommendationService
{
    public function recommend(Campaign $campaign): array
    {
        $budget = $campaign->budget ?: 50000;

        return [
            'suggested_budget' => round($budget * 1.1, 0),
            'best_posting_time' => 'Weekdays 6:00 PM - 9:00 PM',
            'suggested_audience' => 'High-intent buyers in your top cities',
            'expected_cpl' => $campaign->costPerLead() ?: 25,
            'expected_leads' => max(20, (int) $campaign->target_leads ?: 80),
            'expected_qualified_rate' => $campaign->qualifiedRate() ?: 30,
        ];
    }
}
