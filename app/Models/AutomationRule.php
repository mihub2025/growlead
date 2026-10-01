<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'name', 'trigger', 'conditions', 'actions',
        'status', 'created_by', 'last_run_at', 'runs_count',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }
}
