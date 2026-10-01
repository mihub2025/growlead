<?php

namespace App\Services\AI;

class ReportInsightService
{
    public function takeaways(array $report): array
    {
        return [
            'Qualified pipeline is moving, focus on high-score leads first.',
            'Review sources with high spend and low qualified rate.',
            'Follow up overdue tasks to protect SLA.',
        ];
    }

    public function actions(array $report): array
    {
        return [
            'Reallocate budget toward the best converting source.',
            'Assign unassigned leads before the SLA window closes.',
            'Create a re-engagement automation for cold leads.',
        ];
    }
}
