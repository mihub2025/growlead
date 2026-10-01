<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeForOrganization(Builder $query, ?int $organizationId = null): Builder
    {
        $organizationId ??= auth()->user()?->organization_id;

        if ($organizationId) {
            $query->where($query->getModel()->getTable().'.organization_id', $organizationId);
        }

        return $query;
    }
}
