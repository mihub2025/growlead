<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offering extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'name', 'type', 'category', 'description',
        'price', 'currency', 'status', 'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function interests(): HasMany
    {
        return $this->hasMany(LeadInterest::class);
    }
}
