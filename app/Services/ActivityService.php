<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Support\Str;

class ActivityService
{
    public function log(Lead $lead, string $type, ?string $description = null, ?User $user = null, array $extra = []): LeadActivity
    {
        $lead->forceFill(['last_activity_at' => now()])->saveQuietly();

        if (! $lead->first_response_at && in_array($type, ['call', 'whatsapp', 'sms', 'email', 'meeting'], true)) {
            $lead->forceFill([
                'first_response_at' => now(),
                'sla_status' => now()->lessThanOrEqualTo($lead->sla_deadline_at ?? now()) ? 'safe' : 'breached',
            ])->saveQuietly();
        }

        return LeadActivity::create([
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
            'user_id' => $user?->id,
            'type' => $type,
            'channel' => $extra['channel'] ?? $type,
            'subject' => $extra['subject'] ?? Str::headline(str_replace('_', ' ', $type)),
            'description' => $description,
            'metadata' => $extra['metadata'] ?? null,
            'activity_at' => $extra['activity_at'] ?? now(),
        ]);
    }
}
