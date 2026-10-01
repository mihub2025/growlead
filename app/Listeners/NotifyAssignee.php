<?php

namespace App\Listeners;

use App\Events\LeadAssigned;
use App\Notifications\GenericCrmNotification;

class NotifyAssignee
{
    public function handle(LeadAssigned $event): void
    {
        $event->lead->assignedUser?->notify(new GenericCrmNotification(
            'New lead assigned',
            $event->lead->full_name.' has been assigned to you.',
            route('crm.leads.show', $event->lead),
            'leads',
            ['lead_id' => $event->lead->id]
        ));
    }
}
