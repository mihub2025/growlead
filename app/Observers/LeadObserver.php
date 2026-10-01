<?php

namespace App\Observers;

use App\Models\Lead;
use App\Models\LeadAssignment;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        if (! $lead->assigned_user_id) {
            return;
        }

        $this->writeHistory($lead, null, (int) $lead->assigned_user_id, $lead->created_by);

        $updates = [];
        if (! $lead->last_assigned_at) {
            $updates['last_assigned_at'] = now();
        }
        if ((int) $lead->times_assigned < 1) {
            $updates['times_assigned'] = 1;
        }
        if ($updates) {
            $lead->forceFill($updates)->saveQuietly();
        }
    }

    public function updated(Lead $lead): void
    {
        if (! $lead->wasChanged('assigned_user_id') || ! $lead->assigned_user_id) {
            return;
        }

        $from = $lead->getOriginal('assigned_user_id');
        $this->writeHistory($lead, $from ? (int) $from : null, (int) $lead->assigned_user_id, auth()->id());

        $lead->forceFill([
            'last_assigned_at' => now(),
            'times_assigned' => (int) $lead->times_assigned + 1,
        ])->saveQuietly();
    }

    protected function writeHistory(Lead $lead, ?int $fromUserId, int $toUserId, ?int $assignedBy): void
    {
        LeadAssignment::create([
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'assigned_by' => $assignedBy,
            'assigned_at' => now(),
        ]);
    }
}
