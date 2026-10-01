<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(Request $request, TaskService $tasks)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'type' => ['nullable', 'string'],
            'lead_id' => ['nullable', 'integer'],
            'assigned_user_id' => ['nullable', 'integer'],
            'priority' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);
        $tasks->create($request->user()->organization, $data, $request->user());

        return back()->with('success', 'Task created.');
    }

    public function complete(Task $task, TaskService $tasks)
    {
        abort_unless($task->organization_id === auth()->user()->organization_id, 403);
        $tasks->complete($task);

        return back()->with('success', 'Task completed.');
    }
}
