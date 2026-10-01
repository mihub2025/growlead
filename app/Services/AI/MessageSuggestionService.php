<?php

namespace App\Services\AI;

use App\Models\Lead;

class MessageSuggestionService
{
    public function draft(Lead $lead, string $channel = 'whatsapp'): string
    {
        $name = $lead->first_name ?: $lead->full_name;
        $interest = $lead->interested_in ?: 'our offering';

        if ($channel === 'email') {
            return "Hi {$name},\n\nThank you for your interest in {$interest}. I would like to share a few options that match your requirements".($lead->budget_label !== '—' ? ' and budget of '.$lead->budget_label : '').". When is a good time to connect?\n\nBest regards";
        }

        return "Hi {$name}, thanks for your interest in {$interest}. I have a few options that look like a strong fit. Would you like me to share details?";
    }
}
