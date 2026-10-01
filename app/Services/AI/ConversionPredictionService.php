<?php

namespace App\Services\AI;

use App\Models\Lead;

class ConversionPredictionService
{
    public function predict(Lead $lead): string
    {
        $score = (int) ($lead->conversion_probability ?: $lead->lead_score);
        $lead->forceFill(['conversion_probability' => $score])->saveQuietly();

        if ($score >= 70) return 'high';
        if ($score >= 40) return 'medium';

        return 'low';
    }
}
