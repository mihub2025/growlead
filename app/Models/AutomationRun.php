<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AutomationRun extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'automation_rule_id', 'subject_type', 'subject_id',
        'status', 'input', 'output', 'error', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
