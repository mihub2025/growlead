<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->owns($user, $task) && $task->status !== 'completed';
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->createdBy($user, $task);
    }

    public function complete(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    protected function owns(User $user, Task $task): bool
    {
        if ((int) $user->organization_id !== (int) $task->organization_id) {
            return false;
        }

        return (int) $task->assigned_user_id === (int) $user->id
            || (int) $task->created_by === (int) $user->id
            || $user->isAdministrator();
    }

    protected function createdBy(User $user, Task $task): bool
    {
        if ((int) $user->organization_id !== (int) $task->organization_id) {
            return false;
        }

        return (int) $task->created_by === (int) $user->id || $user->isAdministrator();
    }
}
