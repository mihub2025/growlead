<?php

namespace App\Services\AI;

use App\Models\AiInsight;
use App\Models\Lead;

class LeadSummaryService
{
    public function __construct(protected AIProviderInterface $provider)
    {
    }

    public function summarize(Lead $lead): string
    {
        $cached = AiInsight::where('subject_type', Lead::class)->where('subject_id', $lead->id)->where('type', 'summary')->first();
        if ($cached && $cached->isFresh()) {
            return $cached->content;
        }

        $fallback = $lead->full_name.' is interested in '.($lead->interested_in ?: 'your offering')
            .' with a budget of '.$lead->budget_label
            .($lead->location ? ' in '.$lead->location : '')
            .'. Current stage: '.($lead->stage?->name ?? 'New')
            .'. Priority: '.$lead->priority.'.';

        $ai = config('services.ai.enabled') ? $this->provider->complete('Summarize this CRM lead: '.$fallback) : null;
        $content = $ai ?: $fallback;

        AiInsight::updateOrCreate(
            ['organization_id' => $lead->organization_id, 'subject_type' => Lead::class, 'subject_id' => $lead->id, 'type' => 'summary'],
            ['content' => $content, 'generated_at' => now(), 'expires_at' => now()->addDay(), 'provider' => config('services.ai.provider')]
        );

        return $content;
    }
}
