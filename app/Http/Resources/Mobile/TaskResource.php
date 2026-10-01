<?php

namespace App\Http\Resources\Mobile;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = TaskType::tryFrom((string) $this->type);
        $priority = TaskPriority::tryFrom((string) $this->priority);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'type_label' => $type?->label() ?? Str::headline(str_replace('_', ' ', (string) $this->type)),
            'priority' => $this->priority,
            'priority_label' => $priority?->label() ?? Str::headline((string) $this->priority),
            'status' => $this->status,
            'due_at' => $this->due_at?->toIso8601String(),
            'reminder_at' => $this->reminder_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_overdue' => $this->isOverdue(),
            'lead' => $this->lead ? [
                'id' => $this->lead->id,
                'name' => $this->lead->full_name,
            ] : null,
            'created_by' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,
            'can_edit' => (int) $this->created_by === (int) $request->user()?->id && $this->status !== 'completed',
            'can_delete' => (int) $this->created_by === (int) $request->user()?->id,
        ];
    }
}
