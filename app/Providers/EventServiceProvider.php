<?php

namespace App\Providers;

use App\Events\CampaignLeadReceived;
use App\Events\LeadAssigned;
use App\Events\LeadCreated;
use App\Events\LeadStageChanged;
use App\Events\LeadStatusChanged;
use App\Events\OpportunityWon;
use App\Events\TaskOverdue;
use App\Listeners\NotifyAssignee;
use App\Listeners\QueueMetaCapiOnLeadStatusChange;
use App\Listeners\RunAutomations;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        LeadCreated::class => [RunAutomations::class],
        LeadAssigned::class => [RunAutomations::class, NotifyAssignee::class],
        LeadStageChanged::class => [RunAutomations::class],
        LeadStatusChanged::class => [RunAutomations::class, QueueMetaCapiOnLeadStatusChange::class],
        CampaignLeadReceived::class => [RunAutomations::class],
        TaskOverdue::class => [RunAutomations::class],
        OpportunityWon::class => [RunAutomations::class],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
