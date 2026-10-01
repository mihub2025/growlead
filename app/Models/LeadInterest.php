<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadInterest extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'lead_id', 'offering_id', 'name', 'category',
        'estimated_value', 'notes', 'metadata',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }
}
