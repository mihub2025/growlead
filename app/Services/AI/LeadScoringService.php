<?php

namespace App\Services\AI;

use App\Models\AiInsight;
use App\Models\Lead;

class LeadScoringService
{
    public function score(Lead $lead): int
    {
        $score = 20;
        if ($lead->email) $score += 10;
        if ($lead->phone) $score += 10;
        if ($lead->whatsapp) $score += 5;
        if ($lead->company) $score += 5;
        if ($lead->max_budget) $score += 15;
        if ($lead->interested_in) $score += 10;
        if ($lead->city) $score += 5;
        if ($lead->requirement) $score += 10;
        if (in_array($lead->priority, ['high', 'urgent'], true)) $score += 10;

        $score = min(99, $score);
        $lead->forceFill(['lead_score' => $score])->saveQuietly();

        AiInsight::updateOrCreate(
            ['organization_id' => $lead->organization_id, 'subject_type' => Lead::class, 'subject_id' => $lead->id, 'type' => 'lead_score'],
            ['content' => 'Rule-based quality score', 'score' => $score, 'generated_at' => now(), 'expires_at' => now()->addDay()]
        );

        return $score;
    }
}
