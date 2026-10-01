<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id', 'lead_id', 'campaign_id', 'opportunity_id',
        'assigned_user_id', 'created_by', 'type', 'title', 'description',
        'priority', 'status', 'due_at', 'reminder_at', 'completed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'reminder_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'completed' && $this->due_at && $this->due_at->isPast();
    }

    public function scopeIncomplete($query)
    {
        return $query->whereNotIn('status', config('crm.completed_task_statuses', ['completed', 'cancelled', 'done']));
    }
}
