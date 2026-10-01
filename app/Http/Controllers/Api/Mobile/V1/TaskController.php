<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Requests\Mobile\StoreTaskRequest;
use App\Http\Requests\Mobile\UpdateTaskRequest;
use App\Http\Resources\Mobile\TaskResource;
use App\Models\Lead;
use App\Models\Task;
use App\Services\Mobile\AgentAccessService;
use App\Services\TaskService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TaskController extends MobileController
{
    public function __construct(
        protected AgentAccessService $access,
        protected TaskService $tasks
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = $this->access->taskQuery($user);
        $tab = $request->get('tab', 'open');
        $filter = $request->get('filter');

        if ($tab === 'completed') {
            $query->whereIn('status', config('crm.completed_task_statuses', ['completed', 'cancelled', 'done']));
        } else {
            $query->incomplete();
        }

        match ($filter) {
            'today' => $query->whereDate('due_at', now()->toDateString()),
            'upcoming', 'future' => $query->where('due_at', '>', now()->endOfDay()),
            'overdue' => $query->whereNotNull('due_at')->where('due_at', '<', now())->incomplete(),
            default => null,
        };

        $items = $query->orderByRaw("case when status = 'completed' then 1 else 0 end")
            ->orderBy('due_at')
            ->paginate(min((int) $request->get('per_page', 20), 50));

        return TaskResource::collection($items)->additional(['success' => true]);
    }

    public function store(StoreTaskRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $leadId = $data['lead_id'] ?? null;

        if ($leadId) {
            $lead = Lead::query()->findOrFail($leadId);
            $this->access->authorizeLead($user, $lead);
        }

        $dueAt = Carbon::parse($data['due_at']);
        $task = $this->tasks->create($user->organization, [
            'lead_id' => $leadId,
            'assigned_user_id' => $user->id,
            'type' => $data['type'] ?? 'other',
            'title' => $data['title'],
            'description' => $data['notes'] ?? $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'due_at' => $dueAt,
            'reminder_at' => $this->reminderAt($dueAt, $data['reminder'] ?? 'none'),
            'status' => 'open',
        ], $user);

        return $this->ok(new TaskResource($task->load(['lead', 'assignedUser', 'creator'])), 'Task created.', 201);
    }

    public function show(Request $request, Task $task)
    {
        $this->access->authorizeTask($request->user(), $task);

        return $this->ok(new TaskResource($task->load(['lead', 'assignedUser', 'creator'])));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $user = $request->user();
        $this->access->authorizeTask($user, $task);
        abort_unless((int) $task->created_by === (int) $user->id || $user->isAdministrator(), 403);
        abort_if($task->status === 'completed', 422, 'Completed tasks cannot be edited.');

        $data = $request->validated();
        if (! empty($data['lead_id'])) {
            $lead = Lead::query()->findOrFail($data['lead_id']);
            $this->access->authorizeLead($user, $lead);
        }

        $payload = [
            'title' => $data['title'] ?? $task->title,
            'type' => $data['type'] ?? $task->type,
            'priority' => $data['priority'] ?? $task->priority,
            'description' => $data['notes'] ?? $data['description'] ?? $task->description,
        ];
        if (array_key_exists('lead_id', $data)) {
            $payload['lead_id'] = $data['lead_id'];
        }
        if (! empty($data['due_at'])) {
            $dueAt = Carbon::parse($data['due_at']);
            $payload['due_at'] = $dueAt;
            $payload['reminder_at'] = $this->reminderAt($dueAt, $data['reminder'] ?? 'none');
        }

        $task->update($payload);

        return $this->ok(new TaskResource($task->fresh(['lead', 'assignedUser', 'creator'])), 'Task updated.');
    }

    public function complete(Request $request, Task $task)
    {
        $this->access->authorizeTask($request->user(), $task);
        $this->tasks->complete($task);

        return $this->ok(new TaskResource($task->fresh(['lead', 'assignedUser', 'creator'])), 'Task completed.');
    }

    public function destroy(Request $request, Task $task)
    {
        $user = $request->user();
        $this->access->authorizeTask($user, $task);
        abort_unless((int) $task->created_by === (int) $user->id || $user->isAdministrator(), 403);
        $task->delete();

        return $this->ok(null, 'Task deleted.');
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
