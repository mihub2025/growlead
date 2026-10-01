<?php

namespace App\Services;

use App\Models\DuplicateCandidate;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class DuplicateService
{
    public function detect(Lead $lead): void
    {
        $query = Lead::forOrganization($lead->organization_id)
            ->where('id', '!=', $lead->id);

        $matches = $query->where(function ($q) use ($lead) {
            if ($lead->phone_normalized) {
                $q->orWhere('phone_normalized', $lead->phone_normalized)
                    ->orWhere('whatsapp_normalized', $lead->phone_normalized);
            }
            if ($lead->whatsapp_normalized) {
                $q->orWhere('whatsapp_normalized', $lead->whatsapp_normalized)
                    ->orWhere('phone_normalized', $lead->whatsapp_normalized);
            }
            if ($lead->email) {
                $q->orWhere('email', $lead->email);
            }
            if ($lead->external_id) {
                $q->orWhere('external_id', $lead->external_id);
            }
        })->limit(10)->get();

        foreach ($matches as $match) {
            $fields = [];
            if ($lead->phone_normalized && in_array($lead->phone_normalized, [$match->phone_normalized, $match->whatsapp_normalized], true)) {
                $fields[] = 'phone';
            }
            if ($lead->email && strcasecmp((string) $lead->email, (string) $match->email) === 0) {
                $fields[] = 'email';
            }
            if ($lead->external_id && $lead->external_id === $match->external_id) {
                $fields[] = 'external_id';
            }

            DuplicateCandidate::firstOrCreate(
                [
                    'organization_id' => $lead->organization_id,
                    'lead_id' => $lead->id,
                    'possible_duplicate_id' => $match->id,
                ],
                [
                    'confidence' => min(99, 40 + (count($fields) * 25)),
                    'matching_fields' => $fields,
                    'status' => 'pending',
                ]
            );
        }
    }

    public function merge(Lead $primary, Lead $duplicate, $actor = null): Lead
    {
        return DB::transaction(function () use ($primary, $duplicate, $actor) {
            foreach (['email', 'phone', 'whatsapp', 'company', 'interested_in', 'city', 'area'] as $field) {
                if (empty($primary->{$field}) && ! empty($duplicate->{$field})) {
                    $primary->{$field} = $duplicate->{$field};
                }
            }
            $primary->save();

            $duplicate->activities()->update(['lead_id' => $primary->id]);
            $duplicate->notesList()->update(['lead_id' => $primary->id]);
            $duplicate->tasks()->update(['lead_id' => $primary->id]);
            $duplicate->attachments()->update([
                'attachable_id' => $primary->id,
                'attachable_type' => Lead::class,
            ]);
            $duplicate->opportunities()->update(['lead_id' => $primary->id]);
            $primary->tags()->syncWithoutDetaching($duplicate->tags()->pluck('tags.id'));

            DuplicateCandidate::where(function ($q) use ($primary, $duplicate) {
                $q->where('lead_id', $primary->id)->where('possible_duplicate_id', $duplicate->id);
            })->orWhere(function ($q) use ($primary, $duplicate) {
                $q->where('lead_id', $duplicate->id)->where('possible_duplicate_id', $primary->id);
            })->update([
                'status' => 'merged',
                'reviewed_by' => $actor?->id,
                'reviewed_at' => now(),
            ]);

            $duplicate->delete();

            app(AuditService::class)->log('lead.merged', $primary, ['duplicate_id' => $duplicate->id], $primary->toArray(), $actor);

            return $primary->fresh();
        });
    }
}
