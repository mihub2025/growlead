<?php

namespace App\Services;

use App\Events\LeadAssigned;
use App\Events\LeadCreated;
use App\Events\LeadStageChanged;
use App\Events\LeadStatusChanged;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadService
{
    public function __construct(
        protected ActivityService $activities,
        protected DuplicateService $duplicates,
        protected AuditService $audit
    ) {
    }

    public function create(Organization $organization, array $data, ?User $actor = null): Lead
    {
        return DB::transaction(function () use ($organization, $data, $actor) {
            $pipeline = $organization->defaultPipeline();
            $stageId = $data['pipeline_stage_id'] ?? $pipeline?->stages()->orderBy('position')->value('id');

            $lead = Lead::create(array_merge($data, [
                'uuid' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'pipeline_id' => $data['pipeline_id'] ?? $pipeline?->id,
                'pipeline_stage_id' => $stageId,
                'created_by' => $actor?->id,
                'currency' => $organization->currencyCode(),
                'phone_normalized' => Lead::normalizePhone($data['phone'] ?? null),
                'whatsapp_normalized' => Lead::normalizePhone($data['whatsapp'] ?? $data['phone'] ?? null),
                'sla_deadline_at' => now()->addMinutes($organization->sla_minutes ?: 15),
                'sla_status' => 'safe',
                'last_activity_at' => now(),
            ]));

            if (! empty($data['tags'])) {
                $lead->tags()->sync($data['tags']);
            }

            $this->activities->log($lead, 'lead_created', 'Lead created', $actor);
            $this->duplicates->detect($lead);
            $this->audit->log('lead.created', $lead, null, $lead->toArray(), $actor);

            LeadCreated::dispatch($lead, $actor);

            if ($lead->assigned_user_id) {
                LeadAssigned::dispatch($lead, $actor);
            }

            return $lead->fresh(['tags', 'source', 'campaign', 'assignedUser', 'stage']);
        });
    }

    public function update(Lead $lead, array $data, ?User $actor = null): Lead
    {
        return DB::transaction(function () use ($lead, $data, $actor) {
            $before = $lead->toArray();
            $oldStage = $lead->pipeline_stage_id;
            $oldStatus = $lead->status;
            $oldAssignee = $lead->assigned_user_id;

            $data['currency'] = $lead->organization?->currencyCode() ?: $lead->currency;
            $lead->fill(Arr::except($data, ['tags']));
            $lead->save();

            if (array_key_exists('tags', $data)) {
                $lead->tags()->sync($data['tags'] ?? []);
            }

            if ($oldStage != $lead->pipeline_stage_id) {
                $this->activities->log($lead, 'stage_changed', 'Stage updated', $actor);
                LeadStageChanged::dispatch($lead, $actor);
            }
            if ($oldStatus != $lead->status) {
                $this->activities->log($lead, 'status_changed', 'Status updated', $actor);
                LeadStatusChanged::dispatch(
                    $lead,
                    $actor,
                    (string) $oldStatus,
                    (string) $lead->status,
                    now()
                );
            }
            if ($oldAssignee != $lead->assigned_user_id) {
                $this->activities->log($lead, $oldAssignee ? 'reassignment' : 'assignment', 'Lead assigned', $actor);
                LeadAssigned::dispatch($lead, $actor);
            }

            $this->audit->log('lead.updated', $lead, $before, $lead->toArray(), $actor);

            return $lead->fresh(['tags', 'source', 'campaign', 'assignedUser', 'stage']);
        });
    }

    public function storeAttachment(Lead $lead, UploadedFile $file, ?User $actor = null): void
    {
        $path = $file->store('attachments/leads/'.$lead->id, 'local');
        $lead->attachments()->create([
            'organization_id' => $lead->organization_id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $path,
            'disk' => 'local',
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $actor?->id,
        ]);
        $this->activities->log($lead, 'file', 'Attachment uploaded: '.$file->getClientOriginalName(), $actor);
    }

    public function bulkAssign(array $ids, int $userId, Organization $organization, ?User $actor = null): int
    {
        $leads = Lead::forOrganization($organization->id)->whereIn('id', $ids)->get();
        foreach ($leads as $lead) {
            $this->update($lead, ['assigned_user_id' => $userId], $actor);
        }

        return $leads->count();
    }

    public function bulkStatus(array $ids, string $status, Organization $organization, ?User $actor = null): int
    {
        $leads = Lead::forOrganization($organization->id)->whereIn('id', $ids)->get();
        foreach ($leads as $lead) {
            $this->update($lead, ['status' => $status], $actor);
        }

        return $leads->count();
    }

    public function bulkStage(array $ids, int $stageId, Organization $organization, ?User $actor = null): int
    {
        $leads = Lead::forOrganization($organization->id)->whereIn('id', $ids)->get();
        foreach ($leads as $lead) {
            $this->update($lead, ['pipeline_stage_id' => $stageId], $actor);
        }

        return $leads->count();
    }

    public function bulkTags(array $ids, array $tagIds, Organization $organization): int
    {
        $leads = Lead::forOrganization($organization->id)->whereIn('id', $ids)->get();
        foreach ($leads as $lead) {
            $lead->tags()->syncWithoutDetaching($tagIds);
        }

        return $leads->count();
    }
}
