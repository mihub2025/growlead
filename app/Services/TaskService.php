<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;

class TaskService
{
    public function create(Organization $organization, array $data, ?User $actor = null): Task
    {
        return Task::create(array_merge($data, [
            'organization_id' => $organization->id,
            'created_by' => $actor?->id,
            'status' => $data['status'] ?? 'open',
        ]));
    }

    public function complete(Task $task): Task
    {
        $task->update(['status' => 'completed', 'completed_at' => now()]);

        return $task;
    }
}
