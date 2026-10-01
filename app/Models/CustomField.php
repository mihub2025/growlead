<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'entity_type', 'section', 'name', 'slug', 'type',
        'required', 'options', 'default_value', 'position', 'active',
    ];

    protected $casts = [
        'required' => 'boolean',
        'active' => 'boolean',
        'options' => 'array',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }
}
