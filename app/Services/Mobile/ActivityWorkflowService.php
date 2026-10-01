<?php

namespace App\Services\Mobile;

use App\Enums\CallOutcome;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\LeadService;
use App\Services\TaskService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityWorkflowService
{
    public function __construct(
        protected ActivityService $activities,
        protected LeadService $leads,
        protected TaskService $tasks
    ) {
    }

    public function record(Lead $lead, User $actor, array $data): LeadActivity
    {
        return DB::transaction(function () use ($lead, $actor, $data) {
            $type = (string) $data['type'];
            $outcome = $data['outcome'] ?? null;
            $notes = $data['notes'] ?? $data['description'] ?? null;

            $activity = $this->activities->log($lead, $type, $notes, $actor, [
                'channel' => $data['channel'] ?? $type,
                'subject' => $data['subject'] ?? null,
                'metadata' => array_filter([
                    'outcome' => $outcome,
                    'status' => $data['status'] ?? null,
                    'source' => 'mobile',
                ]),
            ]);

            if (! empty($data['status'])) {
                $this->leads->update($lead, ['status' => (string) $data['status']], $actor);
            } else {
                $this->applyOutcomeStatus($lead, $actor, $type, $outcome);
            }

            if (! empty($data['schedule_follow_up']) && ! empty($data['follow_up_at'])) {
                $this->scheduleFollowUp($lead, $actor, [
                    'type' => $data['follow_up_type'] ?? 'call',
                    'due_at' => $data['follow_up_at'],
                    'reminder' => $data['reminder'] ?? 'none',
                    'notes' => $data['follow_up_notes'] ?? $notes,
                ], false);
            }

            return $activity->fresh(['user', 'lead']);
        });
    }

    public function addNote(Lead $lead, User $actor, string $note): LeadActivity
    {
        \App\Models\LeadNote::create([
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
            'user_id' => $actor->id,
            'note' => $note,
        ]);

        return $this->activities->log($lead, 'note', $note, $actor, [
            'metadata' => ['source' => 'mobile'],
        ]);
    }

    public function scheduleFollowUp(Lead $lead, User $actor, array $data, bool $logActivity = true): array
    {
        $dueAt = Carbon::parse($data['due_at']);
        $reminderAt = $this->reminderAt($dueAt, $data['reminder'] ?? 'none');
        $type = $data['type'] ?? 'follow_up';
        $title = $data['title'] ?? ('Follow up with '.$lead->full_name);

        $task = $this->tasks->create($actor->organization, [
            'lead_id' => $lead->id,
            'assigned_user_id' => $actor->id,
            'type' => $type,
            'title' => $title,
            'description' => $data['notes'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'due_at' => $dueAt,
            'reminder_at' => $reminderAt,
            'status' => 'open',
        ], $actor);

        $lead->forceFill(['next_followup_at' => $dueAt])->saveQuietly();

        $activity = null;
        if ($logActivity) {
            $activity = $this->activities->log(
                $lead,
                'follow_up',
                $data['notes'] ?? 'Follow-up scheduled for '.$dueAt->toDayDateTimeString(),
                $actor,
                [
                    'metadata' => [
                        'source' => 'mobile',
                        'task_id' => $task->id,
                        'due_at' => $dueAt->toIso8601String(),
                    ],
                ]
            );
        }

        return compact('task', 'activity');
    }

    protected function applyOutcomeStatus(Lead $lead, User $actor, string $type, ?string $outcome): void
    {
        $outcome = $outcome ? strtolower($outcome) : null;
        if (! $outcome) {
            return;
        }

        $map = config('mobile.activity_status_map', []);
        $newStatus = $map[$outcome] ?? null;
        if (! $newStatus) {
            return;
        }

        if ($outcome === CallOutcome::NoAnswer->value) {
            $current = strtolower((string) $lead->status);
            $newish = array_map('strtolower', config('mobile.new_lead_statuses', []));
            if (! in_array($current, $newish, true)) {
                return;
            }
        }

        if ($outcome === CallOutcome::Interested->value) {
            $lost = array_map('strtolower', config('mobile.lost_statuses', []));
            $qualified = array_map('strtolower', config('mobile.qualified_statuses', []));
            $current = strtolower((string) $lead->status);
            if (in_array($current, $lost, true) || in_array($current, $qualified, true) || in_array($current, array_map('strtolower', config('mobile.working_statuses', [])), true)) {
                if (in_array($current, $qualified, true) || in_array($current, array_map('strtolower', config('mobile.working_statuses', [])), true)) {
                    return;
                }
            }
        }

        $payload = ['status' => $newStatus];
        if ($outcome === CallOutcome::Qualified->value && $lead->pipeline_id) {
            $stage = PipelineStage::where('pipeline_id', $lead->pipeline_id)
                ->where('slug', 'qualified')
                ->first();
            if ($stage) {
                $payload['pipeline_stage_id'] = $stage->id;
            }
        }

        $this->leads->update($lead, $payload, $actor);
    }

    protected function reminderAt(Carbon $dueAt, string $reminder): ?Carbon
    {
        $minutes = config('mobile.reminder_offsets.'.$reminder);
        if (! $minutes) {
            return null;
        }

        return $dueAt->copy()->subMinutes((int) $minutes);
    }
}
