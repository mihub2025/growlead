<?php

namespace App\Listeners;

use App\Services\AutomationService;

class RunAutomations
{
    public function __construct(protected AutomationService $automations)
    {
    }

    public function handle(object $event): void
    {
        $map = [
            \App\Events\LeadCreated::class => 'lead_created',
            \App\Events\LeadAssigned::class => 'lead_assigned',
            \App\Events\LeadStageChanged::class => 'stage_changed',
            \App\Events\LeadStatusChanged::class => 'status_changed',
            \App\Events\CampaignLeadReceived::class => 'campaign_lead_received',
            \App\Events\TaskOverdue::class => 'task_overdue',
            \App\Events\OpportunityWon::class => 'opportunity_won',
        ];

        $trigger = $map[$event::class] ?? null;
        $subject = $event->lead ?? $event->task ?? $event->opportunity ?? null;
        if ($trigger && $subject) {
            $this->automations->runTrigger($trigger, $subject);
        }
    }
}
