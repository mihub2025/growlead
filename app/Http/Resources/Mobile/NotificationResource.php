<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = is_array($this->data) ? $this->data : [];
        $category = $data['category'] ?? $this->inferCategory($data['title'] ?? '');

        return [
            'id' => $this->id,
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'category' => $category,
            'lead_id' => $data['lead_id'] ?? null,
            'task_id' => $data['task_id'] ?? null,
            'url' => $data['url'] ?? null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    protected function inferCategory(string $title): string
    {
        $title = strtolower($title);
        if (str_contains($title, 'lead')) {
            return 'leads';
        }
        if (str_contains($title, 'follow')) {
            return 'follow-ups';
        }
        if (str_contains($title, 'task')) {
            return 'tasks';
        }

        return 'system';
    }
}
