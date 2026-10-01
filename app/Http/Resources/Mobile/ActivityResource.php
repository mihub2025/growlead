<?php

namespace App\Http\Resources\Mobile;

use App\Enums\CallOutcome;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $outcome = data_get($this->metadata, 'outcome');
        $outcomeLabel = null;
        if ($outcome && $enum = CallOutcome::tryFrom((string) $outcome)) {
            $outcomeLabel = $enum->label();
        } elseif ($outcome) {
            $outcomeLabel = Str::headline(str_replace('_', ' ', (string) $outcome));
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => match ((string) $this->type) {
                'call' => 'Call made',
                'whatsapp' => 'WhatsApp sent',
                'email' => 'Email sent',
                'note' => 'Note added',
                'meeting' => 'Meeting',
                'follow_up' => 'Follow-up scheduled',
                'status_changed' => 'Status changed',
                'lead_created' => 'Lead added',
                'other' => filled($this->description)
                    ? (string) $this->description
                    : 'Activity',
                default => Str::headline(str_replace('_', ' ', (string) $this->type)),
            },
            'channel' => $this->channel,
            'subject' => $this->subject,
            'description' => $this->description,
            'outcome' => $outcome,
            'outcome_label' => $outcomeLabel,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null,
            'activity_at' => $this->activity_at?->toIso8601String(),
        ];
    }
}
