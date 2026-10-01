<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'name', 'color', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class);
    }
}
