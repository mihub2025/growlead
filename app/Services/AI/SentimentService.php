<?php

namespace App\Services\AI;

use App\Models\Lead;

class SentimentService
{
    public function analyze(Lead $lead): string
    {
        $score = (int) $lead->lead_score;
        $sentiment = $score >= 75 ? 'positive' : ($score >= 45 ? 'neutral' : 'cold');
        $lead->forceFill(['sentiment' => $sentiment])->saveQuietly();

        return $sentiment;
    }
}
