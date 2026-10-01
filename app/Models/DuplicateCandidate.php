<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuplicateCandidate extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'lead_id', 'possible_duplicate_id', 'confidence',
        'matching_fields', 'status', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'matching_fields' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function duplicate(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'possible_duplicate_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
