<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiInsight extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'subject_type', 'subject_id', 'type', 'provider',
        'model', 'content', 'score', 'metadata', 'generated_at', 'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function isFresh(): bool
    {
        return ! $this->expires_at || $this->expires_at->isFuture();
    }
}
