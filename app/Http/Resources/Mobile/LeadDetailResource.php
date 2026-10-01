<?php

namespace App\Http\Resources\Mobile;

use App\Services\AI\MessageSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $messages = app(MessageSuggestionService::class);
        $payload = $this->metaFormPayload();

        return (new LeadResource($this))->toArray($request) + [
            'job_title' => $this->job_title,
            'city' => $this->city,
            'area' => $this->area,
            'country' => $this->country,
            'interested_in' => $this->interested_in,
            'preferred_contact_method' => $this->preferred_contact_method,
            'form' => [
                'name' => $payload['form_name'] ?? $this->interested_in,
            ],
            'page' => [
                'name' => $payload['page_name'] ?? null,
            ],
            'form_answers' => $this->metaFormRows(),
            'facebook_source' => $this->isMetaLead() ? array_filter([
                'ad_name' => $payload['ad_name'] ?? $payload['ad_id'] ?? null,
                'adset_name' => $payload['adset_name'] ?? $payload['adset_id'] ?? null,
                'campaign_name' => $this->campaign?->name ?? ($payload['campaign_name'] ?? null),
                'form_name' => $payload['form_name'] ?? $this->interested_in,
                'created_at' => $this->meta_created_at?->toIso8601String()
                    ?? (isset($payload['created_time']) ? (string) $payload['created_time'] : $this->created_at?->toIso8601String()),
            ], fn ($value) => filled($value)) : null,
            'is_meta_lead' => $this->isMetaLead(),
            'whatsapp_draft' => $this->whatsappDraft($messages, $request),
            'email_draft' => $messages->draft($this->resource, 'email'),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'recent_activity' => ActivityResource::collection($this->whenLoaded('activities')),
            'notes' => $this->whenLoaded('notesList', function () {
                return $this->notesList->map(fn ($note) => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'user' => $note->user?->name,
                    'created_at' => $note->created_at?->toIso8601String(),
                ]);
            }),
        ];
    }

    protected function whatsappDraft(MessageSuggestionService $messages, Request $request): string
    {
        $agent = $request->user();
        $org = $agent?->organization?->name ?: 'our team';
        $name = $this->first_name ?: $this->full_name;

        return "Hi {$name}, this is {$agent?->name} from {$org}. Thank you for your interest".($this->interested_in ? " in {$this->interested_in}" : '').'. When is a good time to connect?';
    }
}
