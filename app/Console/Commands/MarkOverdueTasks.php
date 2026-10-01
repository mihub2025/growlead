<?php

namespace App\Console\Commands;

use App\Events\TaskOverdue;
use App\Models\Task;
use App\Notifications\GenericCrmNotification;
use Illuminate\Console\Command;

class MarkOverdueTasks extends Command
{
    protected $signature = 'crm:overdue-tasks';
    protected $description = 'Notify owners of overdue tasks';

    public function handle(): int
    {
        Task::where('status', '!=', 'completed')->where('due_at', '<', now())->chunkById(200, function ($tasks) {
            foreach ($tasks as $task) {
                TaskOverdue::dispatch($task);
                $task->assignedUser?->notify(new GenericCrmNotification(
                    'Task overdue',
                    $task->title.' is overdue.',
                    null,
                    'tasks',
                    ['task_id' => $task->id]
                ));
            }
        });

        return self::SUCCESS;
    }
}
