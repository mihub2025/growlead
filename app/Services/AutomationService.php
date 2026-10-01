<?php

namespace App\Services;

use App\Models\AutomationRule;
use App\Models\AutomationRun;
use App\Models\Lead;
use App\Models\Task;
use App\Notifications\GenericCrmNotification;
use Illuminate\Database\Eloquent\Model;

class AutomationService
{
    public function runTrigger(string $trigger, Model $subject): void
    {
        $orgId = $subject->organization_id ?? null;
        if (! $orgId) {
            return;
        }

        $rules = AutomationRule::forOrganization($orgId)->where('trigger', $trigger)->where('status', 'active')->get();

        foreach ($rules as $rule) {
            if (! $this->matches($rule, $subject)) {
                continue;
            }
            $this->execute($rule, $subject);
        }
    }

    protected function matches(AutomationRule $rule, Model $subject): bool
    {
        foreach ($rule->conditions ?? [] as $condition) {
            $field = $condition['field'] ?? null;
            $op = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;
            if (! $field) {
                continue;
            }
            $actual = data_get($subject, $field);
            $ok = match ($op) {
                '!=', '<>' => $actual != $value,
                '>' => $actual > $value,
                '<' => $actual < $value,
                'contains' => str_contains((string) $actual, (string) $value),
                default => $actual == $value,
            };
            if (! $ok) {
                return false;
            }
        }

        return true;
    }

    protected function execute(AutomationRule $rule, Model $subject): void
    {
        $run = AutomationRun::create([
            'organization_id' => $rule->organization_id,
            'automation_rule_id' => $rule->id,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'status' => 'running',
            'input' => ['trigger' => $rule->trigger],
            'started_at' => now(),
        ]);

        try {
            foreach ($rule->actions ?? [] as $action) {
                $this->applyAction($action, $subject);
            }
            $rule->forceFill(['last_run_at' => now(), 'runs_count' => $rule->runs_count + 1])->save();
            $run->update(['status' => 'success', 'finished_at' => now(), 'output' => ['ok' => true]]);
        } catch (\Throwable $e) {
            $run->update(['status' => 'failed', 'error' => $e->getMessage(), 'finished_at' => now()]);
        }
    }

    protected function applyAction(array $action, Model $subject): void
    {
        $type = $action['type'] ?? null;

        if ($subject instanceof Lead) {
            match ($type) {
                'assign_user' => $subject->update(['assigned_user_id' => $action['user_id'] ?? $subject->assigned_user_id]),
                'assign_team' => $subject->update(['assigned_team_id' => $action['team_id'] ?? $subject->assigned_team_id]),
                'change_stage' => $subject->update(['pipeline_stage_id' => $action['stage_id'] ?? $subject->pipeline_stage_id]),
                'change_status' => $subject->update(['status' => $action['status'] ?? $subject->status]),
                'add_tag' => $subject->tags()->syncWithoutDetaching([$action['tag_id'] ?? 0]),
                'create_task' => Task::create([
                    'organization_id' => $subject->organization_id,
                    'lead_id' => $subject->id,
                    'assigned_user_id' => $subject->assigned_user_id,
                    'type' => $action['task_type'] ?? 'follow_up',
                    'title' => $action['title'] ?? 'Automated follow-up',
                    'priority' => $action['priority'] ?? 'medium',
                    'status' => 'open',
                    'due_at' => now()->addHours((int) ($action['due_hours'] ?? 4)),
                ]),
                'notify' => $subject->assignedUser?->notify(new GenericCrmNotification(
                    'Automation',
                    $action['message'] ?? 'An automation ran on '.$subject->full_name
                )),
                default => null,
            };
        }
    }
}
