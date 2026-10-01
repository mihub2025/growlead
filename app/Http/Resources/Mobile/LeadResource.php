<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lastActivity = $this->relationLoaded('activities') ? $this->activities->first() : null;

        return [
            'id' => $this->id,
            'name' => $this->full_name,
            'initials' => $this->initials(),
            'email' => $this->email,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp ?: $this->phone,
            'company' => $this->company,
            'status' => [
                'slug' => $this->status,
                'label' => $this->statusLabel(),
            ],
            'stage' => $this->stage ? [
                'id' => $this->stage->id,
                'name' => $this->stage->name,
                'slug' => $this->stage->slug,
            ] : null,
            'source' => $this->source ? [
                'id' => $this->source->id,
                'name' => $this->source->name,
                'slug' => $this->source->slug,
            ] : null,
            'campaign' => $this->campaign ? [
                'id' => $this->campaign->id,
                'name' => $this->campaign->name,
            ] : null,
            'assigned_agent' => $this->assignedUser ? [
                'id' => $this->assignedUser->id,
                'name' => $this->assignedUser->name,
            ] : null,
            'last_activity' => $lastActivity ? [
                'type' => $lastActivity->type,
                'label' => Str::headline(str_replace('_', ' ', (string) $lastActivity->type)),
                'at' => optional($lastActivity->activity_at)->toIso8601String(),
            ] : ($this->last_activity_at ? [
                'type' => null,
                'label' => 'Updated',
                'at' => $this->last_activity_at->toIso8601String(),
            ] : null),
            'next_follow_up' => $this->next_followup_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
